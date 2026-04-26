@extends('layouts.app', ['title' => 'Ordine '.$order->number.' - Printaqui'])

@section('content')
    <section class="section">
        <div class="wrap">
            <div class="panel">
                <div class="section-head">
                    <div>
                        <h2>{{ $order->number }}</h2>
                        <p class="muted">Ordine effettuato con {{ $order->customer?->email }}</p>
                    </div>
                    @if ($order->tracking_url)
                        <a class="button" href="{{ $order->tracking_url }}" target="_blank" rel="noreferrer">Segui spedizione</a>
                    @endif
                </div>

                <div class="order-status-grid top-margin-large">
                    <div>
                        <span class="muted">Pagamento</span>
                        <strong>{{ $order->payment_status }}</strong>
                    </div>
                    <div>
                        <span class="muted">Evasione</span>
                        <strong>{{ $order->statusLabel() }}</strong>
                    </div>
                    <div>
                        <span class="muted">Totale</span>
                        <strong>€ {{ number_format($order->total_cents / 100, 2, ',', '.') }}</strong>
                    </div>
                    <div>
                        <span class="muted">Tracking</span>
                        <strong>{{ $order->tracking_number ? strtoupper($order->carrier).' '.$order->tracking_number : 'Non ancora disponibile' }}</strong>
                    </div>
                </div>
            </div>

            <div class="panel" style="margin-top:16px;">
                <h2 style="font-size:22px;">Articoli</h2>
                @foreach ($order->items as $item)
                    <div style="border-top:1px solid var(--line); padding:14px 0;">
                        <strong>{{ $item->product_name }}</strong>
                        <div class="muted">{{ $item->product_sku }} - {{ $item->quantity }} pezzi - € {{ number_format($item->line_total_cents / 100, 2, ',', '.') }}</div>

                        <div class="top-margin-small">
                            @foreach ($item->variant_quantities as $variant)
                                <div>{{ $variant['size'] }} / {{ $variant['color'] }}: {{ $variant['quantity'] }}</div>
                            @endforeach
                        </div>

                        @if (count($item->print_zones ?? []))
                            <div class="top-margin-small">
                                @foreach ($item->print_zones as $zone)
                                    <span class="pill">{{ $zone['name'] }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
