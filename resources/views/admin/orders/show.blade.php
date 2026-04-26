@extends('layouts.admin', ['title' => $order->number])

@section('content')
    <div class="top">
        <div>
            <h2>{{ $order->number }}</h2>
            <div class="muted">{{ $order->customer?->email }} - {{ $order->statusLabel() }}</div>
        </div>
        @unless ($order->isFulfilled())
            <form method="post" action="{{ route('admin.orders.fulfill', $order) }}">
                @csrf
                @method('patch')
                <button>Marca evaso</button>
            </form>
        @endunless
    </div>

    <div class="panel">
        <div class="metric">€ {{ number_format($order->total_cents / 100, 2, ',', '.') }}</div>
        <div class="muted">
            Subtotale € {{ number_format($order->subtotal_cents / 100, 2, ',', '.') }} -
            Sconto € {{ number_format($order->discount_cents / 100, 2, ',', '.') }}@if($order->discount_code) ({{ $order->discount_code }})@endif -
            Spedizione € {{ number_format($order->shipping_cents / 100, 2, ',', '.') }}
        </div>
        <div class="muted top-margin-small">
            Pagamento: {{ $order->paymentStatusLabel() }}
            @if ($order->paid_at)
                il {{ $order->paid_at->format('d/m/Y H:i') }}
            @endif
            -
            Stock: {{ $order->stock_decremented_at ? 'scalato il '.$order->stock_decremented_at->format('d/m/Y H:i') : 'non ancora scalato' }}
        </div>
        @if ($order->refunded_at)
            <div class="muted top-margin-small">
                Rimborso: € {{ number_format($order->refunded_cents / 100, 2, ',', '.') }}
                il {{ $order->refunded_at->format('d/m/Y H:i') }}
                @if ($order->stripe_refund_id)
                    - {{ $order->stripe_refund_id }}
                @endif
            </div>
        @endif
        <div class="muted top-margin-small">
            Email conferma:
            @if ($order->order_confirmation_sent_at)
                inviata il {{ $order->order_confirmation_sent_at->format('d/m/Y H:i') }}
            @elseif ($order->order_confirmation_failed_at)
                fallita il {{ $order->order_confirmation_failed_at->format('d/m/Y H:i') }}
            @else
                non ancora inviata
            @endif
        </div>
        <div class="muted top-margin-small">
            Tracking:
            @if ($order->tracking_notification_sent_at)
                email inviata il {{ $order->tracking_notification_sent_at->format('d/m/Y H:i') }}
            @elseif ($order->tracking_notification_failed_at)
                email fallita il {{ $order->tracking_notification_failed_at->format('d/m/Y H:i') }}
            @else
                non ancora notificato
            @endif
        </div>
        @if ($order->internal_notes)
            <div class="errors top-margin-mid">{{ $order->internal_notes }}</div>
        @endif
    </div>

    <div class="panel" style="margin-top:16px;">
        <h2 style="font-size:22px;">Dati checkout</h2>

        @php
            $shippingAddress = $order->shipping_address ?? [];
            $billingAddress = $order->billing_address ?? [];
            $field = fn ($value) => filled($value) ? $value : 'Non indicato';
        @endphp

        <div class="checkout-data-grid top-margin-large">
            <div>
                <span class="muted">Cliente</span>
                <strong>{{ $field(trim(($order->customer?->first_name ?? '').' '.($order->customer?->last_name ?? ''))) }}</strong>
                <div>{{ $field($order->customer?->email) }}</div>
                <div>{{ $field($order->customer?->phone) }}</div>
            </div>

            <div>
                <span class="muted">Indirizzo spedizione</span>
                <strong>{{ $field($shippingAddress['address'] ?? null) }}</strong>
                <div>{{ $field($shippingAddress['postal_code'] ?? null) }} {{ $field($shippingAddress['city'] ?? null) }}</div>
                <div>{{ $field($shippingAddress['country'] ?? null) }}</div>
            </div>

            <div>
                <span class="muted">Indirizzo fatturazione</span>
                <strong>{{ $field($billingAddress['address'] ?? null) }}</strong>
                <div>{{ $field($billingAddress['postal_code'] ?? null) }} {{ $field($billingAddress['city'] ?? null) }}</div>
                <div>{{ $field($billingAddress['country'] ?? null) }}</div>
            </div>
        </div>
    </div>

    <div class="panel" style="margin-top:16px;">
        <div class="section-head">
            <div>
                <h2 style="font-size:22px;">Gestione interna</h2>
                <p class="muted">Note, tag e tracking stile Shopify: scegli corriere, inserisci codice e parte la mail Brevo.</p>
            </div>
            @if ($order->tracking_url)
                <a class="button secondary" href="{{ $order->tracking_url }}" target="_blank" rel="noreferrer">Apri tracking</a>
            @endif
        </div>

        <form method="post" action="{{ route('admin.orders.update', $order) }}">
            @csrf
            @method('patch')

            <div class="form-grid">
                <div class="row" style="grid-column:1/-1">
                    <label>Note interne</label>
                    <textarea name="internal_notes" rows="5" placeholder="Indicazioni produzione, problemi file, richieste cliente...">{{ old('internal_notes', $order->internal_notes) }}</textarea>
                </div>

                <div class="row">
                    <label>Stato ordine</label>
                    <select name="status" required>
                        @foreach ($orderStatuses as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', $order->status) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="row">
                    <label>Tag</label>
                    <input name="tags" value="{{ old('tags', implode(', ', $order->tags ?? [])) }}" placeholder="bulk, urgente, verifica file">
                    <small class="muted">Separa i tag con una virgola.</small>
                </div>

                <div class="row">
                    <label>Corriere</label>
                    <select name="carrier">
                        <option value="">Nessun tracking</option>
                        @foreach ($carriers as $key => $name)
                            <option value="{{ $key }}" @selected(old('carrier', $order->carrier) === $key)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="row">
                    <label>Codice tracking</label>
                    <input name="tracking_number" value="{{ old('tracking_number', $order->tracking_number) }}" placeholder="Numero spedizione">
                </div>
            </div>

            <button class="top-margin-mid">Salva gestione ordine</button>
        </form>
    </div>

    <div class="panel" style="margin-top:16px;">
        <div class="section-head">
            <div>
                <h2 style="font-size:22px;">Rimborso Stripe</h2>
                <p class="muted">Esegue un rimborso completo dell'ordine e registra il riferimento Stripe.</p>
            </div>
        </div>

        @if ($order->payment_status === 'paid' && $order->stripe_payment_intent_id && ! $order->refunded_at)
            <form method="post" action="{{ route('admin.orders.refund', $order) }}">
                @csrf
                <div class="form-grid">
                    <div class="row" style="grid-column:1/-1">
                        <label>Motivo rimborso</label>
                        <textarea name="refund_reason" rows="3" placeholder="Es. richiesta cliente, ordine duplicato, problema file...">{{ old('refund_reason') }}</textarea>
                    </div>
                </div>
                <button class="button danger top-margin-mid">Rimborsa € {{ number_format($order->total_cents / 100, 2, ',', '.') }}</button>
            </form>
        @elseif ($order->refunded_at)
            <div class="alert">Ordine gia rimborsato.</div>
        @else
            <div class="muted">Il rimborso e disponibile solo per ordini pagati con PaymentIntent Stripe collegato.</div>
        @endif
    </div>

    <div class="panel" style="margin-top:16px;">
        <h2 style="font-size:22px;">Articoli</h2>
        @foreach ($order->items as $item)
            <div style="border-top:1px solid var(--line); padding:14px 0;">
                <strong>{{ $item->product_name }}</strong>
                <div class="muted">{{ $item->quantity }} pezzi - stampa € {{ number_format($item->print_unit_price_cents / 100, 2, ',', '.') }} cad.</div>
                <div style="margin-top:8px;">
                    @foreach ($item->variant_quantities as $variant)
                        <div>{{ $variant['size'] }} / {{ $variant['color'] }}: {{ $variant['quantity'] }}</div>
                    @endforeach
                </div>
                <div style="margin-top:8px;">
                    @forelse ($item->printFiles as $file)
                        <div class="print-file-row">
                            <span>{{ $file->zone_name }}: <code>{{ $file->original_name }}</code> - {{ round($file->size_bytes / 1024) }} KB</span>
                            <a class="button secondary button-compact" href="{{ route('admin.orders.print-files.download', [$order, $file]) }}">Scarica</a>
                        </div>
                    @empty
                        <span class="muted">Nessun file stampa: ordine blank.</span>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
@endsection
