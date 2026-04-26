<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_rejects_quantity_above_variant_stock(): void
    {
        $product = $this->product();
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PA-STOCK-S',
            'size' => 'S',
            'color' => 'Nero',
            'stock' => 2,
            'is_active' => true,
        ]);

        $this->from(route('products.show', ['product' => $product->slug]))
            ->post(route('cart.add', ['product' => $product->slug]), [
                'variant_quantities' => [
                    $variant->id => 3,
                ],
            ])
            ->assertRedirect(route('products.show', ['product' => $product->slug]))
            ->assertSessionHasErrors('variant_quantities');

        $this->assertNull(session('cart'));
    }

    public function test_cart_rejects_inactive_variant(): void
    {
        $product = $this->product();
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PA-INACTIVE-S',
            'size' => 'S',
            'color' => 'Nero',
            'stock' => 10,
            'is_active' => false,
        ]);

        $this->from(route('products.show', ['product' => $product->slug]))
            ->post(route('cart.add', ['product' => $product->slug]), [
                'variant_quantities' => [
                    $variant->id => 1,
                ],
            ])
            ->assertRedirect(route('products.show', ['product' => $product->slug]))
            ->assertSessionHasErrors('variant_quantities');

        $this->assertNull(session('cart'));
    }

    private function product(): Product
    {
        return Product::create([
            'name' => 'T-shirt stock',
            'slug' => 't-shirt-stock',
            'sku' => 'PA-STOCK',
            'base_price_cents' => 1990,
            'estimated_delivery' => '7-10 giorni lavorativi',
            'is_active' => true,
        ]);
    }
}
