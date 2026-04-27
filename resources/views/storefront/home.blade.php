@extends('layouts.app', ['title' => 'Printaqui - Custom apparel'])

@section('content')
    <section class="hero">
        <div class="wrap">
            <div class="stack-mid">
                <h1>Printaqui</h1>
                <p>Sei alla ricerca di abbigliamento personalizzato per il tuo progetto o per la tua azienda. Non cercare oltre, la più alta qualità al miglior prezzo è firmata PrintaQui.</p>
                <a class="button" href="{{ route('collections.index') }}">Scopri tutte le nostre collezioni</a>
            </div>
            <img src="https://images.unsplash.com/photo-1523398002811-999ca8dec234?auto=format&fit=crop&w=1200&q=80" alt="Felpe e t-shirt personalizzabili">
        </div>
    </section>

    <section class="section">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <h2>Prodotti in evidenza</h2>
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
