<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItemPrintFile;
use App\Services\OrderNotificationService;
use App\Services\StripeCheckoutService;
use App\Services\TrackingLinkService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Throwable;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);

        return view('admin.orders.index', [
            'orders' => $this->filteredOrdersQuery($filters)
                ->latest()
                ->paginate(30)
                ->withQueryString(),
            'filters' => $filters,
            'orderStatuses' => Order::STATUSES,
            'paymentStatuses' => Order::PAYMENT_STATUSES,
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request);
        $filename = 'ordini-printaqui-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($filters) {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'numero_ordine',
                'data',
                'cliente',
                'email',
                'stato_ordine',
                'stato_pagamento',
                'subtotale',
                'sconto',
                'spedizione',
                'totale',
                'rimborsato',
                'corriere',
                'tracking',
                'paese',
                'tag',
            ]);

            $this->filteredOrdersQuery($filters)
                ->oldest()
                ->chunk(200, function ($orders) use ($output) {
                    foreach ($orders as $order) {
                        fputcsv($output, [
                            $order->number,
                            $order->created_at?->format('Y-m-d H:i:s'),
                            trim(($order->customer?->first_name ?? '').' '.($order->customer?->last_name ?? '')),
                            $order->customer?->email,
                            $order->statusLabel(),
                            $order->paymentStatusLabel(),
                            $this->csvEuro($order->subtotal_cents),
                            $this->csvEuro($order->discount_cents),
                            $this->csvEuro($order->shipping_cents),
                            $this->csvEuro($order->total_cents),
                            $this->csvEuro($order->refunded_cents ?? 0),
                            $order->carrier ? strtoupper($order->carrier) : '',
                            $order->tracking_number,
                            data_get($order->shipping_address, 'country'),
                            implode(', ', $order->tags ?? []),
                        ]);
                    }
                });

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(Order $order, TrackingLinkService $trackingLinks)
    {
        $order->load(['customer', 'items.printFiles']);

        return view('admin.orders.show', [
            'order' => $order,
            'carriers' => $trackingLinks->carriers(),
            'orderStatuses' => Order::STATUSES,
        ]);
    }

    public function fulfill(Order $order)
    {
        $order->update([
            'status' => 'fulfilled',
            'fulfilled_at' => $order->fulfilled_at ?: now(),
        ]);

        return back()->with('status', 'Ordine marcato come evaso.');
    }

    public function refund(Request $request, Order $order, StripeCheckoutService $stripe)
    {
        $validated = $request->validate([
            'refund_reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $stripe->refundOrder($order, $validated['refund_reason'] ?? null);
        } catch (RuntimeException|ApiErrorException $exception) {
            return back()->withErrors(['refund' => $exception->getMessage()]);
        }

        return back()->with('status', 'Ordine rimborsato su Stripe.');
    }

    public function update(Request $request, Order $order, TrackingLinkService $trackingLinks, OrderNotificationService $notifications)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::STATUSES))],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'tags' => ['nullable', 'string', 'max:500'],
            'carrier' => ['nullable', 'required_with:tracking_number', Rule::in(array_keys($trackingLinks->carriers()))],
            'tracking_number' => ['nullable', 'string', 'max:120', 'required_with:carrier'],
        ]);

        $carrier = $validated['carrier'] ?? null;
        $trackingNumber = filled($validated['tracking_number'] ?? null) ? trim($validated['tracking_number']) : null;
        $trackingChanged = $order->carrier !== $carrier || $order->tracking_number !== $trackingNumber;

        $order->update([
            'status' => $validated['status'],
            'fulfilled_at' => $validated['status'] === 'fulfilled' ? ($order->fulfilled_at ?: now()) : null,
            'internal_notes' => $validated['internal_notes'] ?? null,
            'tags' => $this->normalizedTags($validated['tags'] ?? null),
            'carrier' => $carrier,
            'tracking_number' => $trackingNumber,
            'tracking_url' => $carrier && $trackingNumber ? $trackingLinks->urlFor($carrier, $trackingNumber) : null,
            'tracking_notification_sent_at' => $trackingChanged ? null : $order->tracking_notification_sent_at,
            'tracking_notification_failed_at' => $trackingChanged ? null : $order->tracking_notification_failed_at,
        ]);

        if ($order->tracking_url && ($trackingChanged || ! $order->tracking_notification_sent_at)) {
            try {
                $notifications->sendTrackingUpdate($order->refresh());
            } catch (Throwable $exception) {
                report($exception);
                $notifications->markTrackingFailed($order);

                return back()->with('status', 'Tracking salvato, ma email Brevo non inviata: '.$exception->getMessage());
            }
        }

        return back()->with('status', 'Dettagli ordine aggiornati.');
    }

    public function downloadPrintFile(Order $order, OrderItemPrintFile $printFile)
    {
        $printFile->load('orderItem');

        abort_unless($printFile->orderItem?->order_id === $order->id, 404);
        abort_unless(Storage::exists($printFile->stored_path), 404);

        return Storage::download($printFile->stored_path, $this->downloadFilename($order, $printFile));
    }

    private function downloadFilename(Order $order, OrderItemPrintFile $printFile): string
    {
        $printFile->loadMissing('orderItem');

        $extension = pathinfo($printFile->original_name, PATHINFO_EXTENSION)
            ?: pathinfo($printFile->stored_path, PATHINFO_EXTENSION)
            ?: 'file';

        return collect([
            $order->number,
            $printFile->orderItem?->product_sku,
            $printFile->zone_name,
        ])
            ->filter()
            ->map(fn ($part) => Str::slug($part))
            ->implode('_').'.'.Str::lower($extension);
    }

    private function normalizedTags(?string $tags): array
    {
        return collect(explode(',', (string) $tags))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->unique(fn ($tag) => Str::lower($tag))
            ->values()
            ->all();
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(array_keys(Order::STATUSES))],
            'payment_status' => ['nullable', Rule::in(array_keys(Order::PAYMENT_STATUSES))],
            'tracking' => ['nullable', Rule::in(['with', 'missing'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
    }

    private function filteredOrdersQuery(array $filters): Builder
    {
        return Order::query()
            ->with('customer')
            ->when(filled($filters['q'] ?? null), function (Builder $query) use ($filters) {
                $search = trim($filters['q']);

                $query->where(function (Builder $query) use ($search) {
                    $query
                        ->where('number', 'like', "%{$search}%")
                        ->orWhere('tracking_number', 'like', "%{$search}%")
                        ->orWhere('discount_code', 'like', "%{$search}%")
                        ->orWhereHas('customer', function (Builder $query) use ($search) {
                            $query
                                ->where('email', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(filled($filters['payment_status'] ?? null), fn (Builder $query) => $query->where('payment_status', $filters['payment_status']))
            ->when(($filters['tracking'] ?? null) === 'with', fn (Builder $query) => $query->whereNotNull('tracking_number'))
            ->when(($filters['tracking'] ?? null) === 'missing', fn (Builder $query) => $query->whereNull('tracking_number'))
            ->when(filled($filters['date_from'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['date_to']));
    }

    private function csvEuro(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '');
    }
}
