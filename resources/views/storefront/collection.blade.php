@extends('layouts.app', ['title' => ($category?->name ?? 'Shop').' - Printaqui'])

@section('content')
    <section class="section">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <h2>{{ $category?->name ?? 'Shop' }}</h2>
                    <p class="muted">Cerca, filtra e configura blank o stampa DTF.</p>
                </div>
                <form method="get" style="display:flex; gap:10px; flex-wrap:wrap;">
                    <input style="width:220px" type="search" name="q" value="{{ request('q') }}" placeholder="Cerca prodotto">
                    <select style="width:180px" name="sort">
                        <option value="">Ordina</option>
                        <option value="price_asc" @selected(request('sort') === 'price_asc')>Prezzo crescente</option>
                        <option value="price_desc" @selected(request('sort') === 'price_desc')>Prezzo decrescente</option>
                    </select>
                    <button>Cerca</button>
                </form>
            </div>
            <div class="grid">
                @forelse ($products as $product)
                    @include('storefront.partials.product-card', ['product' => $product])
                @empty
                    <div class="panel">Nessun prodotto trovato.</div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
