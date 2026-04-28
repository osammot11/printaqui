@php
    $image = $product->primaryMediaUrl() ?? 'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?auto=format&fit=crop&w=900&q=80';
@endphp

<article class="card">
    <a href="{{ route('products.show', $product) }}">
        <img class="product-media ratio-4-5" src="{{ $image }}" alt="{{ $product->name }}">
    </a>
    <div class="stack-small top-margin-mid">
        <h5>{{ $product->name }}</h5>
        <p>€ {{ number_format($product->currentPriceCents() / 100, 2, ',', '.') }}</p>
    </div>
</article>
