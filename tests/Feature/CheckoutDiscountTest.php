<?php

namespace Tests\Feature;

use App\Models\DiscountCode;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Services\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Stripe\PaymentIntent;
use Tests\TestCase;

class CheckoutDiscountTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_apply_coupon_before_payment(): void
    {
        [$product, $variant] = $this->productWithVariant();

        DiscountCode::create([
            'code' => 'WELCOME10',
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession(['cart' => [$this->cartItem($product, $variant, 5)]])
            ->from(route('checkout.show'))
            ->post(route('checkout.coupon.apply'), [
                'coupon' => 'welcome10',
            ]);

        $response->assertRedirect(route('checkout.show'));
        $response->assertSessionHas('checkout_coupon.code', 'WELCOME10');

        $this
            ->withSession([
                'cart' => [$this->cartItem($product, $variant, 5)],
                'checkout_coupon' => ['code' => 'WELCOME10'],
            ])
            ->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Coupon WELCOME10 applicato')
            ->assertSee('- € 10,00')
            ->assertSee('€ 90,00');
    }

    public function test_customer_can_apply_coupon_without_page_reload(): void
    {
        [$product, $variant] = $this->productWithVariant();

        DiscountCode::create([
            'code' => 'WELCOME10',
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
        ]);

        $this
            ->withSession(['cart' => [$this->cartItem($product, $variant, 5)]])
            ->postJson(route('checkout.coupon.apply'), [
                'coupon' => 'welcome10',
            ])
            ->assertOk()
            ->assertJson([
                'code' => 'WELCOME10',
                'discount_cents' => 1000,
                'discount_formatted' => '€ 10,00',
                'subtotal_after_discount_cents' => 9000,
            ]);
    }

    public function test_invalid_ajax_coupon_returns_validation_error_without_redirect(): void
    {
        [$product, $variant] = $this->productWithVariant();

        $this
            ->withSession(['cart' => [$this->cartItem($product, $variant, 1)]])
            ->postJson(route('checkout.coupon.apply'), [
                'coupon' => 'NOPE',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('coupon');
    }

    public function test_customer_can_remove_applied_coupon(): void
    {
        [$product, $variant] = $this->productWithVariant();

        $this
            ->withSession([
                'cart' => [$this->cartItem($product, $variant, 1)],
                'checkout_coupon' => ['code' => 'WELCOME10'],
            ])
            ->post(route('checkout.coupon.remove'))
            ->assertRedirect(route('checkout.show'))
            ->assertSessionMissing('checkout_coupon');
    }

    public function test_customer_can_remove_coupon_without_page_reload(): void
    {
        [$product, $variant] = $this->productWithVariant();

        $this
            ->withSession([
                'cart' => [$this->cartItem($product, $variant, 1)],
                'checkout_coupon' => ['code' => 'WELCOME10'],
            ])
            ->postJson(route('checkout.coupon.remove'))
            ->assertOk()
            ->assertJson([
                'code' => null,
                'discount_cents' => 0,
                'subtotal_after_discount_cents' => 2000,
            ])
            ->assertSessionMissing('checkout_coupon');
    }

    public function test_checkout_applies_percent_coupon_to_order_total(): void
    {
        [$product, $variant] = $this->productWithVariant();
        $shippingRate = ShippingRate::create([
            'name' => 'Italia',
            'country' => 'IT',
            'price_cents' => 500,
            'is_active' => true,
        ]);

        DiscountCode::create([
            'code' => 'WELCOME10',
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
        ]);

        $this->mock(StripeCheckoutService::class, function (MockInterface $mock) {
            $mock->shouldReceive('assertConfigured')->once();
            $mock->shouldReceive('createPaymentIntent')->once()->andReturn(PaymentIntent::constructFrom([
                'id' => 'pi_discount_test',
                'client_secret' => 'pi_discount_test_secret',
            ]));
        });

        $response = $this
            ->withSession(['cart' => [$this->cartItem($product, $variant, 5)]])
            ->post(route('checkout.store'), [
                'email' => 'cliente@example.test',
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'phone' => '123',
                'address' => 'Via Roma 1',
                'city' => 'Milano',
                'postal_code' => '20100',
                'country' => 'IT',
                'shipping_rate_id' => $shippingRate->id,
                'coupon' => 'welcome10',
            ]);

        $order = Order::firstOrFail();

        $response->assertRedirect(route('checkout.pay', $order));
        $this->assertSame(10000, $order->subtotal_cents);
        $this->assertSame(1000, $order->discount_cents);
        $this->assertSame(500, $order->shipping_cents);
        $this->assertSame(9500, $order->total_cents);
        $this->assertSame('WELCOME10', $order->discount_code);
        $this->assertSame(0, DiscountCode::first()->used_count);
    }

    public function test_checkout_uses_applied_coupon_from_session(): void
    {
        [$product, $variant] = $this->productWithVariant();
        $shippingRate = ShippingRate::create([
            'name' => 'Italia',
            'country' => 'IT',
            'price_cents' => 500,
            'is_active' => true,
        ]);

        DiscountCode::create([
            'code' => 'WELCOME10',
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
        ]);

        $this->mock(StripeCheckoutService::class, function (MockInterface $mock) {
            $mock->shouldReceive('assertConfigured')->once();
            $mock->shouldReceive('createPaymentIntent')->once()->andReturn(PaymentIntent::constructFrom([
                'id' => 'pi_discount_session_test',
                'client_secret' => 'pi_discount_session_test_secret',
            ]));
        });

        $response = $this
            ->withSession([
                'cart' => [$this->cartItem($product, $variant, 5)],
                'checkout_coupon' => ['code' => 'WELCOME10'],
            ])
            ->post(route('checkout.store'), [
                'email' => 'cliente@example.test',
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'phone' => '123',
                'address' => 'Via Roma 1',
                'city' => 'Milano',
                'postal_code' => '20100',
                'country' => 'IT',
                'shipping_rate_id' => $shippingRate->id,
                'coupon' => '',
            ]);

        $order = Order::firstOrFail();

        $response->assertRedirect(route('checkout.pay', $order));
        $this->assertSame(1000, $order->discount_cents);
        $this->assertSame('WELCOME10', $order->discount_code);
        $this->assertFalse(session()->has('checkout_coupon'));
    }

    public function test_checkout_rejects_expired_coupon(): void
    {
        [$product, $variant] = $this->productWithVariant();
        $shippingRate = ShippingRate::create([
            'name' => 'Italia',
            'country' => 'IT',
            'price_cents' => 500,
            'is_active' => true,
        ]);

        DiscountCode::create([
            'code' => 'OLD10',
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
            'ends_at' => now()->subDay(),
        ]);

        $this
            ->withSession(['cart' => [$this->cartItem($product, $variant, 1)]])
            ->from(route('checkout.show'))
            ->post(route('checkout.store'), [
                'email' => 'cliente@example.test',
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'address' => 'Via Roma 1',
                'city' => 'Milano',
                'postal_code' => '20100',
                'country' => 'IT',
                'shipping_rate_id' => $shippingRate->id,
                'coupon' => 'OLD10',
            ])
            ->assertRedirect(route('checkout.show'))
            ->assertSessionHasErrors('coupon');

        $this->assertDatabaseCount('orders', 0);
    }

    private function productWithVariant(): array
    {
        $product = Product::create([
            'name' => 'T-shirt coupon',
            'slug' => 't-shirt-coupon',
            'sku' => 'PA-COUPON',
            'base_price_cents' => 2000,
            'estimated_delivery' => '7-10 giorni lavorativi',
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PA-COUPON-S',
            'size' => 'S',
            'color' => 'Nero',
            'stock' => 20,
            'is_active' => true,
        ]);

        return [$product, $variant];
    }

    private function cartItem(Product $product, ProductVariant $variant, int $quantity): array
    {
        return [
            'id' => 'cart-test',
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_sku' => $product->sku,
            'base_unit_price_cents' => 2000,
            'print_unit_price_cents' => 0,
            'quantity' => $quantity,
            'line_total_cents' => 2000 * $quantity,
            'variant_quantities' => [[
                'variant_id' => $variant->id,
                'sku' => $variant->sku,
                'size' => $variant->size,
                'color' => $variant->color,
                'quantity' => $quantity,
            ]],
            'print_zones' => [],
            'print_files' => [],
        ];
    }
}
