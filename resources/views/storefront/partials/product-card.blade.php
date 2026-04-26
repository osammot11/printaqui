@php
    $image = $product->primaryMediaUrl() ?? 'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?auto=format&fit=crop&w=900&q=80';
@endphp

<article class="card">
    <a href="{{ route('products.show', $product) }}">
        <img class="product-media" src="{{ $image }}" alt="{{ $product->name }}">
    </a>
    <div class="card-body">
        <div class="muted">{{ $product->category?->name ?? 'Apparel' }}</div>
        <h3 style="margin:8px 0 0; font-size:20px;">{{ $product->name }}</h3>
        <div class="price">€ {{ number_format($product->currentPriceCents() / 100, 2, ',', '.') }}</div>
    </div>
</article>
