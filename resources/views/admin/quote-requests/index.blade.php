@extends('layouts.admin', ['title' => 'Preventivi'])

@section('content')
    <div class="top">
        <div class="stack-mid">
            <h2>Preventivi</h2>
            <div class="muted">Richieste bulk/custom ricevute dal form storefront.</div>
        </div>
        <a class="button secondary" href="{{ route('quote.create') }}">Apri form pubblico</a>
    </div>

    <form method="get" class="panel top-margin-mid">
        <div class="form-grid">
            <div class="row">
                <label>Cerca</label>
                <input name="q" value="{{ request('q') }}" placeholder="Numero, email, azienda, prodotto...">
            </div>

            <div class="row">
                <label>Stato</label>
                <select name="status">
                    <option value="">Tutti</option>
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="row">
                <label>&nbsp;</label>
                <button class="mobile-fullwidth">Filtra</button>
            </div>
        </div>
    </form>

    <table class="top-margin-large">
        <thead>
            <tr>
                <th>Richiesta</th>
                <th>Cliente</th>
                <th>Prodotto</th>
                <th>Quantita</th>
                <th>Stato</th>
                <th>Email</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($quoteRequests as $quote)
                <tr>
                    <td>
                        <strong>{{ $quote->number }}</strong>
                        <div class="muted">{{ $quote->created_at->format('d/m/Y H:i') }}</div>
                    </td>
                    <td>
                        {{ $quote->fullName() }}
                        <div class="muted">{{ $quote->email }}</div>
                        @if ($quote->company)
                            <div class="muted">{{ $quote->company }}</div>
                        @endif
                    </td>
                    <td>{{ $quote->product_type }}</td>
                    <td>{{ $quote->quantity }}</td>
                    <td>{{ $quote->statusLabel() }}</td>
                    <td>
                        <span class="muted">
                            Admin: {{ $quote->admin_notification_sent_at ? 'ok' : 'non inviata' }}<br>
                            Cliente: {{ $quote->customer_confirmation_sent_at ? 'ok' : 'non inviata' }}
                        </span>
                    </td>
                    <td><a class="button secondary" href="{{ route('admin.quote-requests.show', $quote) }}">Apri</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Nessuna richiesta preventivo ricevuta.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination-row">{{ $quoteRequests->links() }}</div>
@endsection
