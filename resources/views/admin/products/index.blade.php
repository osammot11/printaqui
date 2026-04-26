@extends('layouts.admin', ['title' => 'Prodotti'])

@section('content')
    <div class="top">
        <div class="stack-mid">
            <h2>Prodotti</h2>
            <div class="muted">CRUD prodotti, varianti e zone stampa per prodotto.</div>
        </div>
        <a class="button" href="{{ route('admin.products.create') }}">Nuovo prodotto</a>
    </div>

    <table>
        <thead><tr><th>Prodotto</th><th>SKU</th><th>Prezzo</th><th>Varianti</th><th>Zone</th><th></th></tr></thead>
        <tbody>
            @foreach ($products as $product)
                <tr>
                    <td><strong>{{ $product->name }}</strong><br><span class="muted">{{ $product->category?->name }}</span></td>
                    <td>{{ $product->sku }}</td>
                    <td>€ {{ number_format($product->currentPriceCents() / 100, 2, ',', '.') }}</td>
                    <td>{{ $product->variants->count() }}</td>
                    <td>{{ $product->printZones->count() }}</td>
                    <td><a class="button secondary" href="{{ route('admin.products.edit', $product) }}">Modifica</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top:14px;">{{ $products->links() }}</div>
@endsection
