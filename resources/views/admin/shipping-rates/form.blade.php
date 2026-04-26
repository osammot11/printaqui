@extends('layouts.admin', ['title' => $shippingRate->exists ? 'Modifica spedizione' : 'Nuova spedizione'])

@section('content')
    <div class="top">
        <div>
            <h2>{{ $shippingRate->exists ? 'Modifica spedizione' : 'Nuova spedizione' }}</h2>
            <div class="muted">Gestisci una tariffa fissa mostrata durante il checkout.</div>
        </div>
        <a class="button secondary" href="{{ route('admin.shipping-rates.index') }}">Lista spedizioni</a>
    </div>

    <form method="post" action="{{ $shippingRate->exists ? route('admin.shipping-rates.update', $shippingRate) : route('admin.shipping-rates.store') }}">
        @csrf
        @if ($shippingRate->exists)
            @method('put')
        @endif

        <div class="panel">
            <div class="form-grid">
                <div class="row">
                    <label>Nome tariffa</label>
                    <input name="name" required value="{{ old('name', $shippingRate->name) }}" placeholder="Italia standard">
                </div>

                <div class="row">
                    <label>Paesi ISO opzionali</label>
                    <input name="country_codes" value="{{ old('country_codes', implode(', ', $shippingRate->countryCodes())) }}" placeholder="IT, FR, DE">
                    <small class="muted">Inserisci piu codici separati da virgola. Lascia vuoto per worldwide.</small>
                </div>

                <div class="row">
                    <label>Area interna</label>
                    <input name="zone" value="{{ old('zone', $shippingRate->zone ?: 'worldwide') }}" placeholder="worldwide">
                </div>

                <div class="row">
                    <label>Prezzo</label>
                    <input type="number" step="0.01" min="0" name="price" required value="{{ old('price', $shippingRate->exists ? $shippingRate->price_cents / 100 : 0) }}">
                    <small class="muted">Inserisci il valore in euro, es. 6.90.</small>
                </div>

                <div class="row">
                    <label>Spedizione gratuita</label>
                    <input type="checkbox" name="is_free_shipping" value="1" @checked(old('is_free_shipping', $shippingRate->is_free_shipping))>
                </div>

                <div class="row">
                    <label>Attiva al checkout</label>
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $shippingRate->exists ? $shippingRate->is_active : true))>
                </div>
            </div>
        </div>

        <div class="admin-actions-row">
            <button>Salva spedizione</button>

            @if ($shippingRate->exists && $shippingRate->is_active)
                <button class="button danger" type="submit" form="delete-shipping-rate-form">Disattiva</button>
            @endif
        </div>
    </form>

    @if ($shippingRate->exists && $shippingRate->is_active)
        <form id="delete-shipping-rate-form" method="post" action="{{ route('admin.shipping-rates.destroy', $shippingRate) }}">
            @csrf
            @method('delete')
        </form>
    @endif
@endsection
