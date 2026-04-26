@extends('layouts.app', ['title' => 'Pagamento '.$order->number.' - Printaqui'])

@section('content')
    <section class="section">
        <div class="wrap product-layout">
            <div class="panel">
                <h2>Pagamento</h2>
                <p class="muted top-margin-mid">Ordine {{ $order->number }}</p>
                <div class="price top-margin-large">Totale € {{ number_format($order->total_cents / 100, 2, ',', '.') }}</div>
                <p class="muted top-margin-mid">Inserisci i dati della carta. In modalita test Stripe puoi usare una carta test come 4242 4242 4242 4242.</p>
            </div>

            <form class="panel" id="payment-form">
                <h2 style="font-size:24px;">Carta</h2>
                <div id="payment-element" class="stripe-payment-element top-margin-large"></div>
                <div id="payment-message" class="errors top-margin-mid" hidden></div>
                <button id="submit-payment" class="top-margin-large mobile-fullwidth" type="submit">
                    <span id="button-text">Paga ora</span>
                </button>
            </form>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const stripe = Stripe(@json($stripeKey));
        const elements = stripe.elements({
            clientSecret: @json($clientSecret),
            appearance: {
                theme: 'stripe',
                variables: {
                    colorPrimary: '#0909A2',
                    colorText: '#0F172A',
                    colorDanger: '#DC2626',
                    borderRadius: '8px',
                    fontFamily: 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif'
                }
            }
        });

        const paymentElement = elements.create('payment');
        paymentElement.mount('#payment-element');

        const form = document.getElementById('payment-form');
        const submitButton = document.getElementById('submit-payment');
        const buttonText = document.getElementById('button-text');
        const message = document.getElementById('payment-message');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            submitButton.disabled = true;
            buttonText.textContent = 'Pagamento in corso...';
            message.hidden = true;

            const { error } = await stripe.confirmPayment({
                elements,
                confirmParams: {
                    return_url: @json(route('checkout.thank-you', $order)),
                },
            });

            if (error) {
                message.textContent = error.message || 'Pagamento non riuscito. Riprova.';
                message.hidden = false;
                submitButton.disabled = false;
                buttonText.textContent = 'Paga ora';
            }
        });
    </script>
@endpush
