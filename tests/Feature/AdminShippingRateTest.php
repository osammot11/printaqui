<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\User;
use App\Services\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Stripe\PaymentIntent;
use Tests\TestCase;

class AdminShippingRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_deactivate_shipping_rate(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.shipping-rates.store'), [
                'name' => 'Italia standard',
                'country_codes' => 'it, fr',
                'zone' => 'italy',
                'price' => 6.90,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.shipping-rates.index'));

        $rate = ShippingRate::firstOrFail();

        $this->assertSame('IT', $rate->country_code);
        $this->assertSame(['IT', 'FR'], $rate->country_codes);
        $this->assertSame(690, $rate->price_cents);
        $this->assertTrue($rate->is_active);

        $this->actingAs($admin)
            ->put(route('admin.shipping-rates.update', $rate), [
                'name' => 'Italia gratuita',
                'country_codes' => 'IT, FR, DE',
                'zone' => 'italy',
                'price' => 6.90,
                'is_free_shipping' => '1',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.shipping-rates.edit', $rate));

        $rate->refresh();

        $this->assertSame('Italia gratuita', $rate->name);
        $this->assertSame(['IT', 'FR', 'DE'], $rate->country_codes);
        $this->assertSame(0, $rate->price_cents);
        $this->assertTrue($rate->is_free_shipping);

        $this->actingAs($admin)
            ->delete(route('admin.shipping-rates.destroy', $rate))
            ->assertRedirect(route('admin.shipping-rates.index'));

        $this->assertFalse($rate->refresh()->is_active);
    }

    public function test_admin_shipping_rate_rejects_invalid_country_codes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->from(route('admin.shipping-rates.create'))
            ->post(route('admin.shipping-rates.store'), [
                'name' => 'Europa',
                'country_codes' => 'IT, ITA, 1F',
                'zone' => 'europe',
                'price' => 12.90,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.shipping-rates.create'))
            ->assertSessionHasErrors('country_codes');

        $this->assertDatabaseCount('shipping_rates', 0);
    }

    public function test_checkout_uses_free_shipping_rate_without_shipping_cost(): void
    {
        [$product, $variant] = $this->productWithVariant();
        $shippingRate = ShippingRate::create([
            'name' => 'Free shipping',
            'country_code' => 'IT',
            'zone' => 'italy',
            'price_cents' => 0,
            'is_free_shipping' => true,
            'is_active' => true,
        ]);

        $this->mock(StripeCheckoutService::class, function (MockInterface $mock) {
            $mock->shouldReceive('assertConfigured')->once();
            $mock->shouldReceive('createPaymentIntent')->once()->andReturn(PaymentIntent::constructFrom([
                'id' => 'pi_free_shipping_test',
                'client_secret' => 'pi_free_shipping_test_secret',
            ]));
        });

        $response = $this
            ->withSession(['cart' => [$this->cartItem($product, $variant, 2)]])
            ->post(route('checkout.store'), [
                'email' => 'cliente@example.test',
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'address' => 'Via Roma 1',
                'city' => 'Milano',
                'postal_code' => '20100',
                'country' => 'IT',
                'shipping_rate_id' => $shippingRate->id,
            ]);

        $order = Order::firstOrFail();

        $response->assertRedirect(route('checkout.pay', $order));
        $this->assertSame(0, $order->shipping_cents);
        $this->assertSame(4000, $order->total_cents);
    }

    public function test_checkout_rejects_inactive_shipping_rate(): void
    {
        [$product, $variant] = $this->productWithVariant();
        $shippingRate = ShippingRate::create([
            'name' => 'Inactive shipping',
            'country_code' => 'IT',
            'zone' => 'italy',
            'price_cents' => 500,
            'is_active' => false,
        ]);

        $this->mock(StripeCheckoutService::class, function (MockInterface $mock) {
            $mock->shouldReceive('assertConfigured')->once();
            $mock->shouldNotReceive('createPaymentIntent');
        });

        $this
            ->withSession(['cart' => [$this->cartItem($product, $variant, 1)]])
            ->post(route('checkout.store'), [
                'email' => 'cliente@example.test',
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'address' => 'Via Roma 1',
                'city' => 'Milano',
                'postal_code' => '20100',
                'country' => 'IT',
                'shipping_rate_id' => $shippingRate->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_rejects_shipping_rate_not_available_for_customer_country(): void
    {
        [$product, $variant] = $this->productWithVariant();
        $shippingRate = ShippingRate::create([
            'name' => 'Italia e Francia',
            'country_code' => 'IT',
            'country_codes' => ['IT', 'FR'],
            'zone' => 'europe',
            'price_cents' => 500,
            'is_active' => true,
        ]);

        $this->mock(StripeCheckoutService::class, function (MockInterface $mock) {
            $mock->shouldReceive('assertConfigured')->once();
            $mock->shouldNotReceive('createPaymentIntent');
        });

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
                'country' => 'ES',
                'shipping_rate_id' => $shippingRate->id,
            ])
            ->assertRedirect(route('checkout.show'))
            ->assertSessionHasErrors('shipping_rate_id');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_page_marks_shipping_rates_with_available_country_codes(): void
    {
        [$product, $variant] = $this->productWithVariant();
        ShippingRate::create([
            'name' => 'Italia e Francia',
            'country_code' => 'IT',
            'country_codes' => ['IT', 'FR'],
            'zone' => 'europe',
            'price_cents' => 500,
            'is_active' => true,
        ]);
        ShippingRate::create([
            'name' => 'Worldwide',
            'country_code' => null,
            'country_codes' => null,
            'zone' => 'worldwide',
            'price_cents' => 1500,
            'is_active' => true,
        ]);

        $this
            ->withSession(['cart' => [$this->cartItem($product, $variant, 1)]])
            ->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('data-countries="IT,FR"', false)
            ->assertSee('data-countries=""', false);
    }

    private function productWithVariant(): array
    {
        $product = Product::create([
            'name' => 'T-shirt shipping',
            'slug' => 't-shirt-shipping',
            'sku' => 'PA-SHIPPING',
            'base_price_cents' => 2000,
            'estimated_delivery' => '7-10 giorni lavorativi',
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PA-SHIPPING-S',
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
            'id' => 'shipping-cart-test',
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_sku' => $product->sku,
            'base_unit_price_cents' => $product->base_price_cents,
            'print_unit_price_cents' => 0,
            'quantity' => $quantity,
            'line_total_cents' => $product->base_price_cents * $quantity,
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
