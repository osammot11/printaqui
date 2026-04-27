@extends('layouts.app', ['title' => $product->name.' - Printaqui'])

@section('content')
    <section class="section">
        <div class="wrap product-layout">
            <div>
                @php $primaryImage = $product->primaryMediaUrl() ?? 'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?auto=format&fit=crop&w=1100&q=80'; @endphp
                <img class="product-media product-main-image" style="border-radius:8px; border:1px solid var(--line);" src="{{ $primaryImage }}" alt="{{ $product->name }}" data-main-product-image>
                @if (count($product->galleryMediaUrls()) > 1)
                    <div class="product-gallery top-margin-mid">
                        @foreach ($product->galleryMediaUrls() as $image)
                            <button class="product-gallery-button @if($image === $primaryImage) is-active @endif" type="button" data-gallery-image="{{ $image }}" aria-label="Mostra immagine {{ $loop->iteration }} di {{ $product->name }}">
                                <img src="{{ $image }}" alt="{{ $product->name }} gallery {{ $loop->iteration }}">
                            </button>
                        @endforeach
                    </div>
                @endif
                <div class="panel top-margin-large stack-mid">
                    <h1>{{ $product->name }}</h1>
                    <p class="muted">{{ $product->description }}</p>
                    <div class="pillrow">
                        <span class="pill">{{ $product->estimated_delivery }}</span>
                    </div>
                    @if ($product->sizeChartUrl())
                        <a class="button secondary" href="{{ $product->sizeChartUrl() }}" target="_blank" rel="noreferrer">Tabella taglie</a>
                    @endif
                </div>
            </div>

            <form class="panel" method="post" action="{{ route('cart.add', $product) }}" enctype="multipart/form-data" id="product-configurator">
                @csrf
                <h2>Configura</h2>
                <p class="muted top-margin-small">A partire da {{ number_format($product->currentPriceCents() / 100, 2, ',', '.') }}€</p>

                <h4 class="top-margin-large">Quantita per variante</h4>
                <table class="top-margin-mid">
                    <thead>
                        <tr><th>Taglia</th><th>Colore</th><th>Disponibili</th><th>Qta</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($product->variants as $variant)
                            <tr class="@if($variant->stock <= 0) product-variant-unavailable @endif">
                                <td>{{ $variant->size }}</td>
                                <td>{{ $variant->color }}</td>
                                <td>{{ $variant->stock }}</td>
                                <td><input min="0" max="{{ $variant->stock }}" type="number" name="variant_quantities[{{ $variant->id }}]" value="0" @disabled($variant->stock <= 0)></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">Nessuna variante disponibile.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <h4 class="top-margin-large">Personalizzazione opzionale</h4>
                @foreach ($product->activePrintZones as $zone)
                    <div class="panel top-margin-mid" style="margin-bottom:12px; padding:12px;">
                        <label style="display:flex; align-items:center; gap:8px; margin:0;">
                            <input style="width:auto" type="checkbox" class="zone-toggle" name="print_zones[]" value="{{ $zone->id }}" data-price="{{ $zone->additional_price_cents }}" data-target="file-{{ $zone->id }}">
                            <span>{{ $zone->name }} (+ € {{ number_format($zone->additional_price_cents / 100, 2, ',', '.') }})</span>
                        </label>
                        <div id="file-{{ $zone->id }}" style="display:none; margin-top:10px;">
                            <label>File per {{ $zone->name }}</label>
                            <input type="file" name="print_files[{{ $zone->id }}]" accept=".png,.jpg,.jpeg,.avif,.pdf,.svg">
                        </div>
                    </div>
                @endforeach

                <div class="panel" style="background:#f9fbfa;">
                    <div class="muted">Totale unitario stimato</div>
                    <div class="price" id="unit-price" data-base="{{ $product->currentPriceCents() }}">€ {{ number_format($product->currentPriceCents() / 100, 2, ',', '.') }}</div>
                </div>
                <button class="fullwidth top-margin-large" @disabled($product->variants->isEmpty())>Aggiungi al carrello</button>
            </form>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    const toggles = document.querySelectorAll('.zone-toggle');
    const unitPrice = document.getElementById('unit-price');
    const mainProductImage = document.querySelector('[data-main-product-image]');
    const galleryButtons = document.querySelectorAll('[data-gallery-image]');

    function formatCents(cents) {
        return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(cents / 100);
    }

    function refreshPrice() {
        let total = Number(unitPrice.dataset.base);
        toggles.forEach((toggle) => {
            document.getElementById(toggle.dataset.target).style.display = toggle.checked ? 'block' : 'none';
            if (toggle.checked) total += Number(toggle.dataset.price);
        });
        unitPrice.textContent = formatCents(total);
    }

    toggles.forEach((toggle) => toggle.addEventListener('change', refreshPrice));
    refreshPrice();

    galleryButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (!mainProductImage) return;

            mainProductImage.src = button.dataset.galleryImage;
            galleryButtons.forEach((item) => item.classList.remove('is-active'));
            button.classList.add('is-active');
        });
    });
</script>
@endpush
