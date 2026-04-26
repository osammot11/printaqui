<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\ShippingRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_delivery_estimate_setting(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->patch(route('admin.settings.update'), [
                'delivery_estimate' => '5-7 giorni lavorativi',
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('5-7 giorni lavorativi', Setting::deliveryEstimateLabel());
    }

    public function test_new_product_form_uses_delivery_estimate_setting_as_default(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Setting::putValue('delivery_estimate', ['label' => '3-5 giorni lavorativi']);

        $this->actingAs($admin)
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('3-5 giorni lavorativi');
    }

    public function test_checkout_shows_delivery_estimate_setting(): void
    {
        [$product, $variant] = $this->productWithVariant();
        Setting::putValue('delivery_estimate', ['label' => '4-6 giorni lavorativi']);
        ShippingRate::create([
            'name' => 'Italia standard',
            'country_code' => 'IT',
            'country_codes' => ['IT'],
            'zone' => 'italy',
            'price_cents' => 690,
            'is_active' => true,
        ]);

        $this
            ->withSession(['cart' => [$this->cartItem($product, $variant, 1)]])
            ->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Consegna stimata')
            ->assertSee('4-6 giorni lavorativi');
    }

    private function productWithVariant(): array
    {
        $product = Product::create([
            'name' => 'T-shirt settings',
            'slug' => 't-shirt-settings',
            'sku' => 'PA-SETTINGS',
            'base_price_cents' => 2000,
            'estimated_delivery' => '7-10 giorni lavorativi',
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PA-SETTINGS-S',
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
            'id' => 'settings-cart-test',
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
