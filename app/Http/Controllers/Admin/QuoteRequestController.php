<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class QuoteRequestController extends Controller
{
    public function index(Request $request)
    {
        $quoteRequests = QuoteRequest::query()
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('q'), function ($query, $search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%")
                        ->orWhere('product_type', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.quote-requests.index', [
            'quoteRequests' => $quoteRequests,
            'statuses' => QuoteRequest::STATUSES,
        ]);
    }

    public function show(QuoteRequest $quoteRequest)
    {
        return view('admin.quote-requests.show', [
            'quoteRequest' => $quoteRequest,
            'statuses' => QuoteRequest::STATUSES,
        ]);
    }

    public function update(Request $request, QuoteRequest $quoteRequest)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(QuoteRequest::STATUSES))],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $updates = [
            'status' => $validated['status'],
            'internal_notes' => $validated['internal_notes'] ?? null,
        ];

        if (in_array($validated['status'], ['responded', 'won', 'lost'], true) && ! $quoteRequest->responded_at) {
            $updates['responded_at'] = now();
        }

        $quoteRequest->update($updates);

        return back()->with('status', 'Richiesta preventivo aggiornata.');
    }

    public function downloadArtwork(QuoteRequest $quoteRequest)
    {
        abort_unless($quoteRequest->artwork_path && Storage::exists($quoteRequest->artwork_path), 404);

        return Storage::download(
            $quoteRequest->artwork_path,
            $quoteRequest->artwork_original_name ?: $quoteRequest->number.'-allegato'
        );
    }
}
