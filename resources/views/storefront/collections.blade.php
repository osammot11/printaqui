@extends('layouts.app', ['title' => 'Collezioni - Printaqui'])

@section('content')
    <section class="section collections-section">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <h2>Collezioni</h2>
                    <p class="muted">Sfoglia le categorie attive e scegli il punto di partenza piu comodo per il tuo ordine.</p>
                </div>
                <a class="button secondary" href="{{ route('shop.index') }}">Vedi tutto lo shop</a>
            </div>

            <div class="collection-grid">
                @forelse ($categories as $category)
                    @php
                        $previewProduct = $category->activeProducts->first();
                        $image = $previewProduct?->primaryMediaUrl()
                            ?? 'https://images.unsplash.com/photo-1523398002811-999ca8dec234?auto=format&fit=crop&w=900&q=80';
                    @endphp

                    <a class="collection-card" href="{{ route('collections.show', $category->slug) }}">
                        <img src="{{ $image }}" alt="{{ $category->name }}">
                        <span class="collection-card-body">
                            <span>
                                <strong>{{ $category->name }}</strong>
                                @if ($category->description)
                                    <small>{{ $category->description }}</small>
                                @endif
                            </span>
                            <span class="collection-meta">
                                {{ $category->active_products_count }} {{ $category->active_products_count === 1 ? 'prodotto' : 'prodotti' }}
                            </span>
                        </span>
                    </a>
                @empty
                    <div class="panel">Nessuna collezione attiva al momento.</div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
