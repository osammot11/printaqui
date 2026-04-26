@extends('layouts.app', ['title' => 'Ordine '.$order->number])

@section('content')
    <section class="section">
        <div class="wrap">
            <div class="panel">
                <h2>Ordine ricevuto</h2>
                <p class="muted">Numero ordine {{ $order->number }}</p>
                <div class="price">Totale € {{ number_format($order->total_cents / 100, 2, ',', '.') }}</div>
                <p>Stato pagamento: {{ $order->payment_status }}.</p>
                @if ($order->payment_status !== 'paid' && $order->stripe_payment_intent_id)
                    <a class="button" href="{{ route('checkout.pay', $order) }}">Completa pagamento</a>
                @else
                    <a class="button" href="{{ route('orders.lookup') }}">Controlla stato ordine</a>
                @endif
            </div>
        </div>
    </section>
@endsection
