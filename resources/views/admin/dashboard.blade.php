@extends('layouts.admin', ['title' => 'Dashboard Printaqui'])

@section('content')
    <div class="top">
        <div class="stack-mid">
            <h2>Dashboard</h2>
            <div class="muted">Vendite nette, rimborsi, evasione e clienti.</div>
        </div>
        <a class="button" href="{{ route('admin.products.create') }}">Nuovo prodotto</a>
    </div>

    <div class="cards">
        <div class="card"><div class="muted">Net sales</div><div class="metric">€ {{ number_format($netSales / 100, 2, ',', '.') }}</div></div>
        <div class="card"><div class="muted">Ordini revenue</div><div class="metric">{{ $revenueOrderCount }}</div></div>
        <div class="card"><div class="muted">AOV</div><div class="metric">€ {{ number_format($aov / 100, 2, ',', '.') }}</div></div>
        <div class="card"><div class="muted">Da evadere</div><div class="metric">{{ $unfulfilledPaidCount }}</div></div>
    </div>

    <div class="cards">
        <div class="card"><div class="muted">Gross sales</div><div class="metric">€ {{ number_format($grossSales / 100, 2, ',', '.') }}</div></div>
        <div class="card"><div class="muted">Rimborsi</div><div class="metric">€ {{ number_format($refunded / 100, 2, ',', '.') }}</div></div>
        <div class="card"><div class="muted">Pagamenti pending</div><div class="metric">{{ $pendingPaymentCount }}</div></div>
        <div class="card"><div class="muted">Clienti</div><div class="metric">{{ $customerCount }}</div></div>
    </div>

    <div class="panel">
        <h2 style="font-size:22px; margin-bottom:12px;">Ultimi ordini</h2>
        <table>
            <thead><tr><th>Ordine</th><th>Cliente</th><th>Stato</th><th>Pagamento</th><th>Netto</th><th>Totale</th></tr></thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->number }}</a></td>
                        <td>{{ $order->customer?->email }}</td>
                        <td>{{ $order->statusLabel() }}</td>
                        <td>{{ $order->payment_status }}</td>
                        <td>€ {{ number_format(max(0, $order->total_cents - $order->refunded_cents) / 100, 2, ',', '.') }}</td>
                        <td>€ {{ number_format($order->total_cents / 100, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Nessun ordine ancora.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
