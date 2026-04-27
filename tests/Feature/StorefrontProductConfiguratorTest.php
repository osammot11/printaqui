<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontProductConfiguratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_page_groups_bulk_variant_inputs_by_clickable_color_pills(): void
    {
        $product = Product::create([
            'name' => 'T-shirt colori',
            'slug' => 't-shirt-colori',
            'sku' => 'PA-COLORI',
            'base_price_cents' => 1990,
            'estimated_delivery' => '7-10 giorni lavorativi',
            'is_active' => true,
        ]);

        $blackSmall = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PA-COLORI-NERO-S',
            'size' => 'S',
            'color' => 'Nero',
            'hex_color' => '#111111',
            'stock' => 10,
            'is_active' => true,
        ]);

        $blackMedium = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PA-COLORI-NERO-M',
            'size' => 'M',
            'color' => 'Nero',
            'hex_color' => '#111111',
            'stock' => 7,
            'is_active' => true,
        ]);

        $whiteSmall = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PA-COLORI-BIANCO-S',
            'size' => 'S',
            'color' => 'Bianco',
            'hex_color' => '#ffffff',
            'stock' => 5,
            'is_active' => true,
        ]);

        $this
            ->get(route('products.show', ['product' => $product->slug]))
            ->assertOk()
            ->assertSee('data-color-option', false)
            ->assertSee('style="--swatch-color: #111111;"', false)
            ->assertSee('style="--swatch-color: #ffffff;"', false)
            ->assertSee('data-variant-color="color-0"', false)
            ->assertSee('data-variant-color="color-1"', false)
            ->assertSee('Colore selezionato:', false)
            ->assertSee("variant_quantities[{$blackSmall->id}]", false)
            ->assertSee("variant_quantities[{$blackMedium->id}]", false)
            ->assertSee("variant_quantities[{$whiteSmall->id}]", false);
    }
}
