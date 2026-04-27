<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_product_form_starts_without_default_variants(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.products.create'));

        $response->assertOk();
        $response->assertSee('Nessuna variante creata');
        $response->assertDontSee('variants[0][size]', false);
    }

    public function test_new_product_form_starts_without_default_print_zones(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.products.create'));

        $response->assertOk();
        $response->assertSee('Nessuna zona stampa creata');
        $response->assertDontSee('print_zones[0][name]', false);
        $response->assertDontSee('Fronte cuore');
    }

    public function test_product_form_has_color_filter_tools_for_many_variants(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = Product::create([
            'name' => 'T-shirt admin colori',
            'slug' => 't-shirt-admin-colori',
            'sku' => 'PA-ADMIN-COLORI',
            'base_price_cents' => 1990,
            'estimated_delivery' => '7-10 giorni lavorativi',
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PA-ADMIN-COLORI-NERO-S',
            'size' => 'S',
            'color' => 'Nero',
            'hex_color' => '#111111',
            'stock' => 10,
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PA-ADMIN-COLORI-BIANCO-M',
            'size' => 'M',
            'color' => 'Bianco',
            'hex_color' => '#ffffff',
            'stock' => 8,
            'is_active' => true,
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('data-variant-color-toolbar', false)
            ->assertSee('data-variant-color-options', false)
            ->assertSee('data-variant-color-filter="__all"', false)
            ->assertSee('data-variant-color-input', false)
            ->assertSee('data-variant-hex-input', false)
            ->assertSee('data-variant-color="Nero"', false)
            ->assertSee('data-variant-hex="#111111"', false)
            ->assertSee('data-variant-color="Bianco"', false)
            ->assertSee('data-variant-hex="#ffffff"', false);
    }
}
