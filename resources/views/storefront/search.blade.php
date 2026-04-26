@extends('layouts.app', ['title' => 'Cerca - Printaqui'])

@section('content')
    <section class="section">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <h2>Cerca</h2>
                    <p class="muted">Trova prodotti per nome, SKU, descrizione o collezione.</p>
                </div>
                <form method="get" class="search-form">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Cerca prodotto, SKU o categoria" autofocus>
                    <select name="sort">
                        <option value="">Ordina</option>
                        <option value="price_asc" @selected(request('sort') === 'price_asc')>Prezzo crescente</option>
                        <option value="price_desc" @selected(request('sort') === 'price_desc')>Prezzo decrescente</option>
                    </select>
                    <button>Cerca</button>
                </form>
            </div>

            @if ($search === '')
                <div class="panel">Inserisci una parola chiave per cercare nello shop.</div>
            @else
                <div class="muted top-margin-mid">
                    {{ $products->count() }} {{ $products->count() === 1 ? 'risultato' : 'risultati' }} per "{{ $search }}"
                </div>

                <div class="grid top-margin-large">
                    @forelse ($products as $product)
                        @include('storefront.partials.product-card', ['product' => $product])
                    @empty
                        <div class="panel">Nessun prodotto trovato.</div>
                    @endforelse
                </div>
            @endif
        </div>
    </section>
@endsection
