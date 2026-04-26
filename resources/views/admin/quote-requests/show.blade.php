@extends('layouts.admin', ['title' => $quoteRequest->number])

@section('content')
    <div class="top">
        <div>
            <h2>{{ $quoteRequest->number }}</h2>
            <div class="muted">{{ $quoteRequest->fullName() }} - {{ $quoteRequest->statusLabel() }}</div>
        </div>
        <a class="button secondary" href="{{ route('admin.quote-requests.index') }}">Lista preventivi</a>
    </div>

    <div class="panel">
        <div class="checkout-data-grid">
            <div>
                <span class="muted">Cliente</span>
                <strong>{{ $quoteRequest->fullName() }}</strong>
                <div>{{ $quoteRequest->email }}</div>
                <div>{{ $quoteRequest->phone ?: 'Telefono non indicato' }}</div>
                <div>{{ $quoteRequest->company ?: 'Azienda non indicata' }}</div>
            </div>

            <div>
                <span class="muted">Richiesta</span>
                <strong>{{ $quoteRequest->product_type }}</strong>
                <div>{{ $quoteRequest->quantity }} pezzi</div>
                <div>{{ $quoteRequest->print_positions ?: 'Posizioni non indicate' }}</div>
                <div>{{ $quoteRequest->deadline ? 'Deadline '.$quoteRequest->deadline->format('d/m/Y') : 'Deadline non indicata' }}</div>
            </div>

            <div>
                <span class="muted">Email</span>
                <strong>Admin: {{ $quoteRequest->admin_notification_sent_at ? 'inviata' : 'non inviata' }}</strong>
                <div>Cliente: {{ $quoteRequest->customer_confirmation_sent_at ? 'inviata' : 'non inviata' }}</div>
                <div>{{ $quoteRequest->responded_at ? 'Risposto il '.$quoteRequest->responded_at->format('d/m/Y H:i') : 'Non ancora risposto' }}</div>
            </div>
        </div>
    </div>

    <div class="panel" style="margin-top:16px;">
        <h2 style="font-size:22px;">Messaggio cliente</h2>
        <p class="top-margin-mid">{!! nl2br(e($quoteRequest->message)) !!}</p>

        @if ($quoteRequest->artwork_path)
            <div class="top-margin-large">
                <h4>Allegato</h4>
                <div class="print-file-row top-margin-small">
                    <span>
                        <code>{{ $quoteRequest->artwork_original_name }}</code>
                        @if ($quoteRequest->artwork_size_bytes)
                            - {{ round($quoteRequest->artwork_size_bytes / 1024) }} KB
                        @endif
                    </span>
                    <a class="button secondary button-compact" href="{{ route('admin.quote-requests.artwork.download', $quoteRequest) }}">Scarica</a>
                </div>
            </div>
        @endif
    </div>

    <div class="panel" style="margin-top:16px;">
        <h2 style="font-size:22px;">Gestione interna</h2>

        <form method="post" action="{{ route('admin.quote-requests.update', $quoteRequest) }}" class="top-margin-large">
            @csrf
            @method('patch')

            <div class="form-grid">
                <div class="row">
                    <label>Stato</label>
                    <select name="status">
                        @foreach ($statuses as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', $quoteRequest->status) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="row" style="grid-column:1/-1">
                    <label>Note interne</label>
                    <textarea name="internal_notes" rows="6" placeholder="Prezzo proposto, follow-up, dettagli produzione...">{{ old('internal_notes', $quoteRequest->internal_notes) }}</textarea>
                </div>
            </div>

            <button>Salva gestione preventivo</button>
        </form>
    </div>
@endsection
