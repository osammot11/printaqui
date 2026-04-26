<?php

namespace App\Http\Controllers;

use App\Models\QuoteRequest;
use App\Services\BrevoTransactionalEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class QuoteRequestController extends Controller
{
    public function create()
    {
        return view('storefront.quote-request');
    }

    public function store(Request $request, BrevoTransactionalEmailService $brevo)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:60'],
            'company' => ['nullable', 'string', 'max:160'],
            'product_type' => ['required', 'string', 'max:160'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999999'],
            'print_positions' => ['nullable', 'string', 'max:500'],
            'deadline' => ['nullable', 'date'],
            'message' => ['required', 'string', 'max:5000'],
            'artwork' => ['nullable', 'file', 'mimes:png,jpg,jpeg,pdf,svg', 'max:20480'],
        ]);

        $quote = DB::transaction(function () use ($request, $validated) {
            $quote = QuoteRequest::create([
                'number' => 'PQ-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => strtolower($validated['email']),
                'phone' => $validated['phone'] ?? null,
                'company' => $validated['company'] ?? null,
                'product_type' => $validated['product_type'],
                'quantity' => (int) $validated['quantity'],
                'print_positions' => $validated['print_positions'] ?? null,
                'deadline' => $validated['deadline'] ?? null,
                'message' => $validated['message'],
            ]);

            if ($request->hasFile('artwork')) {
                $file = $request->file('artwork');
                $path = $file->store("quote-requests/{$quote->id}");

                $quote->update([
                    'artwork_original_name' => $file->getClientOriginalName(),
                    'artwork_path' => $path,
                    'artwork_mime_type' => $file->getMimeType(),
                    'artwork_size_bytes' => $file->getSize(),
                ]);
            }

            return $quote;
        });

        $this->sendNotifications($quote, $brevo);

        return redirect()
            ->route('quote.create')
            ->with('status', 'Richiesta preventivo ricevuta. Ti abbiamo inviato una conferma via email.');
    }

    private function sendNotifications(QuoteRequest $quote, BrevoTransactionalEmailService $brevo): void
    {
        try {
            if ($brevo->sendQuoteRequestAdminNotification($quote)) {
                $quote->update([
                    'admin_notification_sent_at' => now(),
                    'admin_notification_failed_at' => null,
                ]);
            }
        } catch (Throwable $exception) {
            report($exception);
            $quote->update(['admin_notification_failed_at' => now()]);
        }

        try {
            if ($brevo->sendQuoteRequestCustomerConfirmation($quote)) {
                $quote->update([
                    'customer_confirmation_sent_at' => now(),
                    'customer_confirmation_failed_at' => null,
                ]);
            }
        } catch (Throwable $exception) {
            report($exception);
            $quote->update(['customer_confirmation_failed_at' => now()]);
        }
    }
}
