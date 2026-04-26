@extends('layouts.app', ['title' => 'Printaqui - Custom apparel'])

@section('content')
    <section class="hero">
        <div class="wrap">
            <div class="stack-mid">
                <h1>Printaqui</h1>
                <p>Shop per blank apparel e capi personalizzati DTF con zone di stampa selezionabili, upload file per posizione e ordini bulk per taglia.</p>
                <a class="button" href="{{ route('shop.index') }}">Vai allo shop</a>
            </div>
            <img src="https://images.unsplash.com/photo-1523398002811-999ca8dec234?auto=format&fit=crop&w=1200&q=80" alt="Felpe e t-shirt personalizzabili">
        </div>
    </section>

    <section class="section">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <h2>Prodotti in evidenza</h2>
                    <p class="muted">Prezzo blank, stampa opzionale e configurazione bulk nello stesso flusso.</p>
                </div>
                <a class="button secondary" href="{{ route('shop.index') }}">Tutti i prodotti</a>
            </div>
            <div class="grid">
                @foreach ($products as $product)
                    @include('storefront.partials.product-card', ['product' => $product])
                @endforeach
            </div>
        </div>
    </section>
@endsection
