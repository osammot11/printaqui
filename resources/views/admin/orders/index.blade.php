@extends('layouts.admin', ['title' => 'Ordini'])

@section('content')
    <div class="top">
        <div class="stack-mid">
            <h2>Ordini</h2>
            <div class="muted">Stato evasione, pagamento, file stampa e tracking.</div>
        </div>
        <a class="button secondary" href="{{ route('admin.orders.export', request()->query()) }}">Export CSV</a>
    </div>

    <form class="panel top-margin-large" method="get" action="{{ route('admin.orders.index') }}">
        <div class="form-grid">
            <div class="row">
                <label>Cerca</label>
                <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Ordine, email, cliente, tracking, coupon">
            </div>

            <div class="row">
                <label>Stato ordine</label>
                <select name="status">
                    <option value="">Tutti</option>
                    @foreach ($orderStatuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="row">
                <label>Pagamento</label>
                <select name="payment_status">
                    <option value="">Tutti</option>
                    @foreach ($paymentStatuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['payment_status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="row">
                <label>Tracking</label>
                <select name="tracking">
                    <option value="">Tutti</option>
                    <option value="with" @selected(($filters['tracking'] ?? '') === 'with')>Con tracking</option>
                    <option value="missing" @selected(($filters['tracking'] ?? '') === 'missing')>Da inserire</option>
                </select>
            </div>

            <div class="row">
                <label>Dal</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </div>

            <div class="row">
                <label>Al</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </div>
        </div>

        <div class="top-margin-mid" style="display:flex; gap:10px; flex-wrap:wrap;">
            <button>Filtra ordini</button>
            <a class="button secondary" href="{{ route('admin.orders.index') }}">Reset</a>
        </div>
    </form>

    <table>
        <thead><tr><th>Ordine</th><th>Cliente</th><th>Stato</th><th>Pagamento</th><th>Tracking</th><th>Totale</th><th></th></tr></thead>
        <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td>{{ $order->number }}</td>
                    <td>
                        <strong>{{ trim(($order->customer?->first_name ?? '').' '.($order->customer?->last_name ?? '')) ?: 'Cliente' }}</strong>
                        <div class="muted">{{ $order->customer?->email }}</div>
                    </td>
                    <td>{{ $order->statusLabel() }}</td>
                    <td>{{ $order->paymentStatusLabel() }}</td>
                    <td>{{ $order->tracking_number ? strtoupper($order->carrier).' '.$order->tracking_number : 'Da inserire' }}</td>
                    <td>€ {{ number_format($order->total_cents / 100, 2, ',', '.') }}</td>
                    <td><a class="button secondary" href="{{ route('admin.orders.show', $order) }}">Apri</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Nessun ordine trovato con questi filtri.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div style="margin-top:14px;">{{ $orders->links() }}</div>
@endsection
