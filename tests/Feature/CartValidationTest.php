<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PrintZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_removes_item_when_product_is_no_longer_active(): void
    {
        [$product, $variant] = $this->productWithVariant();
        $product->update(['is_active' => false]);

        $this
            ->withSession(['cart' => [$this->cartItem($product, $variant)]])
            ->get(route('cart.show'))
            ->assertOk()
            ->assertSee('Il carrello e vuoto');

        $this->assertSame([], session('cart'));
    }

    public function test_checkout_redirects_when_cart_item_becomes_invalid(): void
    {
        [$product, $variant] = $this->productWithVariant();
        $variant->update(['is_active' => false]);

        $this
            ->withSession(['cart' => [$this->cartItem($product, $variant)]])
            ->get(route('checkout.show'))
            ->assertRedirect(route('cart.show'));
    }

    public function test_cart_recalculates_current_product_and_print_prices(): void
    {
        [$product, $variant, $zone] = $this->productWithVariantAndZone();
        $product->update(['base_price_cents' => 2500]);
        $zone->update(['additional_price_cents' => 700]);

        $this
            ->withSession(['cart' => [$this->cartItem($product, $variant, $zone)]])
            ->get(route('cart.show'))
            ->assertOk()
            ->assertSee('€ 64,00');

        $cartItem = session('cart')[0];

        $this->assertSame(2500, $cartItem['base_unit_price_cents']);
        $this->assertSame(700, $cartItem['print_unit_price_cents']);
        $this->assertSame(6400, $cartItem['line_total_cents']);
    }

    private function productWithVariant(): array
    {
        $product = Product::create([
            'name' => 'T-shirt validation',
            'slug' => 't-shirt-validation',
            'sku' => 'PA-VALIDATION',
            'base_price_cents' => 2000,
            'estimated_delivery' => '7-10 giorni lavorativi',
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PA-VALIDATION-S',
            'size' => 'S',
            'color' => 'Nero',
            'stock' => 10,
            'is_active' => true,
        ]);

        return [$product, $variant];
    }

    private function productWithVariantAndZone(): array
    {
        [$product, $variant] = $this->productWithVariant();

        $zone = PrintZone::create([
            'product_id' => $product->id,
            'name' => 'Retro',
            'slug' => 'retro',
            'additional_price_cents' => 500,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return [$product, $variant, $zone];
    }

    private function cartItem(Product $product, ProductVariant $variant, ?PrintZone $zone = null): array
    {
        $quantity = 2;
        $printUnitPrice = $zone ? $zone->additional_price_cents : 0;

        return [
            'id' => 'cart-validation',
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_sku' => $product->sku,
            'base_unit_price_cents' => $product->currentPriceCents(),
            'print_unit_price_cents' => $printUnitPrice,
            'quantity' => $quantity,
            'line_total_cents' => ($product->currentPriceCents() + $printUnitPrice) * $quantity,
            'variant_quantities' => [[
                'variant_id' => $variant->id,
                'sku' => $variant->sku,
                'size' => $variant->size,
                'color' => $variant->color,
                'quantity' => $quantity,
            ]],
            'print_zones' => $zone ? [[
                'id' => $zone->id,
                'name' => $zone->name,
                'slug' => $zone->slug,
                'additional_price_cents' => $zone->additional_price_cents,
            ]] : [],
            'print_files' => [],
        ];
    }
}
