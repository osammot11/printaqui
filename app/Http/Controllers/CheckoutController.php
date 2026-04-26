<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Setting;
use App\Models\ShippingRate;
use App\Services\CartValidationService;
use App\Services\DiscountService;
use App\Services\OrderStockService;
use App\Services\StripeCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Stripe\Exception\ApiErrorException;

class CheckoutController extends Controller
{
    public function show(DiscountService $discounts, CartValidationService $cartValidation)
    {
        $cart = $this->validatedCart($cartValidation);
        $items = $cart['items'];

        if ($items->isEmpty()) {
            return redirect()->route('cart.show');
        }

        $subtotal = $items->sum('line_total_cents');
        $appliedCoupon = $this->appliedCoupon($discounts, $subtotal);

        return view('storefront.checkout', [
            'items' => $items,
            'subtotal_cents' => $subtotal,
            'discount_cents' => $appliedCoupon['discount_cents'],
            'appliedCoupon' => $appliedCoupon,
            'cartWarnings' => $cart['errors'],
            'shippingRates' => ShippingRate::where('is_active', true)->orderBy('price_cents')->get(),
            'deliveryEstimate' => Setting::deliveryEstimateLabel(),
        ]);
    }

    public function applyCoupon(Request $request, DiscountService $discounts)
    {
        $items = collect(session('cart', []));

        if ($items->isEmpty()) {
            return redirect()->route('cart.show');
        }

        $validated = $request->validate([
            'coupon' => ['required', 'string', 'max:80'],
        ]);

        $discount = $discounts->resolve($validated['coupon']);
        $discountCents = $discounts->amountForSubtotalCents($discount, $items->sum('line_total_cents'));

        session(['checkout_coupon' => [
            'code' => $discount->code,
        ]]);

        if ($request->expectsJson()) {
            return response()->json($this->couponPayload(
                $discount->code,
                $discountCents,
                $items->sum('line_total_cents'),
                'Coupon '.$discount->code.' applicato.'
            ));
        }

        return redirect()
            ->route('checkout.show')
            ->withInput($request->except('coupon'))
            ->with('status', 'Coupon '.$discount->code.' applicato: - € '.number_format($discountCents / 100, 2, ',', '.'));
    }

    public function removeCoupon(Request $request)
    {
        session()->forget('checkout_coupon');

        if ($request->expectsJson()) {
            $items = collect(session('cart', []));

            return response()->json($this->couponPayload(
                null,
                0,
                $items->sum('line_total_cents'),
                'Coupon rimosso.'
            ));
        }

        return redirect()
            ->route('checkout.show')
            ->withInput($request->except('coupon'))
            ->with('status', 'Coupon rimosso.');
    }

    public function store(Request $request, StripeCheckoutService $stripe, OrderStockService $orderStock, DiscountService $discounts, CartValidationService $cartValidation)
    {
        $cart = $this->validatedCart($cartValidation);
        $items = $cart['items'];

        if ($items->isEmpty()) {
            return redirect()->route('cart.show');
        }

        if ($cart['errors']->isNotEmpty()) {
            return redirect()
                ->route('cart.show')
                ->withErrors(['cart' => 'Il carrello e cambiato. Controlla gli articoli prima di procedere.']);
        }

        try {
            $stripe->assertConfigured();
        } catch (RuntimeException $exception) {
            return back()->withErrors(['stripe' => $exception->getMessage()])->withInput();
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:60'],
            'address' => ['required', 'string', 'max:180'],
            'city' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:30'],
            'country' => ['required', 'string', 'size:2'],
            'shipping_rate_id' => ['required', 'integer'],
            'coupon' => ['nullable', 'string', 'max:80'],
        ]);

        $shippingRate = ShippingRate::where('is_active', true)->findOrFail($validated['shipping_rate_id']);
        if (! $shippingRate->isAvailableForCountry($validated['country'])) {
            throw ValidationException::withMessages([
                'shipping_rate_id' => 'La spedizione selezionata non e disponibile per il paese indicato.',
            ]);
        }

        $orderStock->assertCartIsAvailable($items);

        $subtotal = $items->sum('line_total_cents');
        $discountCode = $discounts->resolve($this->checkoutCouponCode($request));
        $discount = $discountCode ? $discounts->amountForSubtotalCents($discountCode, $subtotal) : 0;
        $shipping = $shippingRate->is_free_shipping ? 0 : $shippingRate->price_cents;

        $order = DB::transaction(function () use ($validated, $items, $subtotal, $discount, $discountCode, $shipping) {
            $address = [
                'address' => $validated['address'],
                'city' => $validated['city'],
                'postal_code' => $validated['postal_code'],
                'country' => strtoupper($validated['country']),
            ];

            $customer = Customer::updateOrCreate(
                ['email' => strtolower($validated['email'])],
                [
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'phone' => $validated['phone'] ?? null,
                    'shipping_address' => $address,
                    'billing_address' => $address,
                ]
            );

            $order = Order::create([
                'customer_id' => $customer->id,
                'number' => 'PA-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'status' => 'unfulfilled',
                'payment_status' => 'pending',
                'discount_code_id' => $discountCode?->id,
                'discount_code' => $discountCode?->code,
                'subtotal_cents' => $subtotal,
                'discount_cents' => $discount,
                'shipping_cents' => $shipping,
                'total_cents' => max(0, $subtotal - $discount) + $shipping,
                'shipping_address' => $address,
                'billing_address' => $address,
                'tags' => array_filter(['checkout-interno', $discountCode?->code]),
            ]);

            foreach ($items as $item) {
                $orderItem = $order->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_quantities' => $item['variant_quantities'],
                    'print_zones' => $item['print_zones'],
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],
                    'base_unit_price_cents' => $item['base_unit_price_cents'],
                    'print_unit_price_cents' => $item['print_unit_price_cents'],
                    'quantity' => $item['quantity'],
                    'line_total_cents' => $item['line_total_cents'],
                ]);

                foreach ($item['print_files'] as $file) {
                    $orderItem->printFiles()->create([
                        'print_zone_id' => $file['zone_id'],
                        'zone_name' => $file['zone_name'],
                        'original_name' => $file['original_name'],
                        'stored_path' => $file['stored_path'],
                        'mime_type' => $file['mime_type'],
                        'size_bytes' => $file['size_bytes'],
                    ]);
                }
            }

            return $order;
        });

        $order->load('customer');

        try {
            $paymentIntent = $stripe->createPaymentIntent($order, $order->customer);
        } catch (ApiErrorException $exception) {
            $order->update(['payment_status' => 'failed']);

            return redirect()
                ->route('checkout.show')
                ->withErrors(['stripe' => 'Stripe non ha creato il pagamento: '.$exception->getMessage()]);
        }

        $order->update([
            'stripe_payment_intent_id' => $paymentIntent->id,
            'payment_status' => 'pending',
        ]);

        session()->forget(['cart', 'checkout_coupon']);

        return redirect()->route('checkout.pay', $order);
    }

    public function pay(Order $order, StripeCheckoutService $stripe)
    {
        if ($order->payment_status === 'paid') {
            return redirect()->route('checkout.thank-you', $order);
        }

        if (! $order->stripe_payment_intent_id) {
            return redirect()
                ->route('checkout.thank-you', $order)
                ->withErrors(['stripe' => 'Questo ordine non ha un pagamento Stripe collegato.']);
        }

        try {
            $paymentIntent = $stripe->retrievePaymentIntent($order->stripe_payment_intent_id);
        } catch (ApiErrorException $exception) {
            return redirect()
                ->route('checkout.thank-you', $order)
                ->withErrors(['stripe' => 'Impossibile recuperare il pagamento Stripe: '.$exception->getMessage()]);
        }

        return view('storefront.payment', [
            'order' => $order,
            'stripeKey' => $stripe->publicKey(),
            'clientSecret' => $paymentIntent->client_secret,
        ]);
    }

    public function thankYou(Request $request, Order $order, StripeCheckoutService $stripe)
    {
        if ($request->filled('payment_intent') && $request->payment_intent === $order->stripe_payment_intent_id) {
            try {
                $paymentIntent = $stripe->retrievePaymentIntent($request->payment_intent);
                $stripe->syncOrderFromPaymentIntent($paymentIntent);
                $order->refresh();
            } catch (ApiErrorException) {
                report('Stripe PaymentIntent non verificabile durante thank-you: '.$request->payment_intent);
            }
        }

        $order->load(['customer', 'items.printFiles']);

        return view('storefront.thank-you', compact('order'));
    }

    private function checkoutCouponCode(Request $request): ?string
    {
        return session('checkout_coupon.code') ?: $request->input('coupon');
    }

    private function appliedCoupon(DiscountService $discounts, int $subtotalCents): array
    {
        $code = session('checkout_coupon.code');

        if (blank($code)) {
            return [
                'code' => null,
                'discount_cents' => 0,
            ];
        }

        try {
            $discount = $discounts->resolve($code);
        } catch (\Illuminate\Validation\ValidationException) {
            session()->forget('checkout_coupon');

            return [
                'code' => null,
                'discount_cents' => 0,
            ];
        }

        return [
            'code' => $discount->code,
            'discount_cents' => $discounts->amountForSubtotalCents($discount, $subtotalCents),
        ];
    }

    private function couponPayload(?string $code, int $discountCents, int $subtotalCents, string $message): array
    {
        $subtotalAfterDiscount = max(0, $subtotalCents - $discountCents);

        return [
            'message' => $message,
            'code' => $code,
            'discount_cents' => $discountCents,
            'discount_formatted' => $this->formatEuro($discountCents),
            'subtotal_after_discount_cents' => $subtotalAfterDiscount,
            'subtotal_after_discount_formatted' => $this->formatEuro($subtotalAfterDiscount),
        ];
    }

    private function formatEuro(int $cents): string
    {
        return '€ '.number_format($cents / 100, 2, ',', '.');
    }

    private function validatedCart(CartValidationService $cartValidation): array
    {
        $result = $cartValidation->validate(collect(session('cart', [])));

        if ($result['changed']) {
            session(['cart' => $result['items']->all()]);
        }

        return $result;
    }
}
