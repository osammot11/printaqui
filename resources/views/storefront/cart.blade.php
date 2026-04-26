@extends('layouts.app', ['title' => 'Carrello - Printaqui'])

@section('content')
    <section class="section">
        <div class="wrap">
            <div class="section-head">
                <h2>Carrello</h2>
                <a class="button secondary" href="{{ route('shop.index') }}">Continua shopping</a>
            </div>

            @if ($cart['items']->isEmpty())
                <div class="panel">Il carrello e vuoto.</div>
            @else
                @if (($cartWarnings ?? collect())->isNotEmpty())
                    <div class="errors">
                        @foreach ($cartWarnings as $warning)
                            <div>{{ $warning }}</div>
                        @endforeach
                    </div>
                @endif

                <div>
                        @foreach ($cart['items'] as $item)
                            <div class="card top-margin-large stack-mid">
                                <h4>{{ $item['product_name'] }}</h4>
                                <div>
                                    @foreach ($item['variant_quantities'] as $variant)
                                        <div>{{ $variant['size'] }} / {{ $variant['color'] }}: {{ $variant['quantity'] }}</div>
                                    @endforeach
                                </div>
                                <div>
                                    @forelse ($item['print_zones'] as $zone)
                                        <div>{{ $zone['name'] }}</div>
                                    @empty
                                        <span class="muted">Blank</span>
                                    @endforelse
                                </div>
                                <p>€ {{ number_format($item['line_total_cents'] / 100, 2, ',', '.') }}</p>
                                <div>
                                    <form method="post" action="{{ route('cart.remove', $item['id']) }}">
                                        @csrf
                                        @method('delete')
                                        <button class="button danger">Rimuovi</button>
                                    </form>
                                </div>
                        </div>
                        @endforeach
                </div>

                <div class="panel grid-2 top-margin-large">
                    <div>
                        <div class="muted">{{ $cart['count'] }} pezzi totali</div>
                        <div class="price">Subtotale € {{ number_format($cart['subtotal_cents'] / 100, 2, ',', '.') }}</div>
                    </div>
                    <a class="button" href="{{ route('checkout.show') }}">Checkout</a>
                </div>
            @endif
        </div>
    </section>
@endsection
