@extends('layouts.admin', ['title' => 'Spedizioni'])

@section('content')
    <div class="top">
        <div class="stack-mid">
            <h2>Spedizioni</h2>
            <div class="muted">Tariffe fisse disponibili al checkout, con opzione gratuita e attivazione rapida.</div>
        </div>
        <a class="button" href="{{ route('admin.shipping-rates.create') }}">Nuova tariffa</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Area</th>
                <th>Prezzo</th>
                <th>Stato</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($shippingRates as $rate)
                <tr>
                    <td><strong>{{ $rate->name }}</strong></td>
                    <td>
                        <span class="muted">
                            {{ $rate->isWorldwide() ? 'Worldwide' : implode(', ', $rate->countryCodes()) }}
                            @if ($rate->zone)
                                · {{ $rate->zone }}
                            @endif
                        </span>
                    </td>
                    <td>
                        @if ($rate->is_free_shipping)
                            Gratis
                        @else
                            € {{ number_format($rate->price_cents / 100, 2, ',', '.') }}
                        @endif
                    </td>
                    <td>{{ $rate->is_active ? 'Attiva' : 'Disattivata' }}</td>
                    <td><a class="button secondary" href="{{ route('admin.shipping-rates.edit', $rate) }}">Modifica</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Nessuna tariffa spedizione creata.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination-row">{{ $shippingRates->links() }}</div>
@endsection
