<?php

namespace Tests\Feature;

use App\Models\PrintZone;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_product_after_confirmation(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = $this->product();

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PA-DELETE-S',
            'size' => 'S',
            'color' => 'Nero',
            'stock' => 10,
            'is_active' => true,
        ]);

        PrintZone::create([
            'product_id' => $product->id,
            'name' => 'Fronte',
            'slug' => 'fronte',
            'additional_price_cents' => 500,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this
            ->actingAs($admin)
            ->delete(route('admin.products.destroy', $product), [
                'delete_product_confirmation' => '1',
            ])
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_variants', ['product_id' => $product->id]);
        $this->assertDatabaseMissing('print_zones', ['product_id' => $product->id]);
    }

    public function test_admin_cannot_delete_product_without_confirmation(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = $this->product();

        $this
            ->actingAs($admin)
            ->from(route('admin.products.edit', $product))
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.edit', $product))
            ->assertSessionHasErrors('delete_product_confirmation');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    private function product(): Product
    {
        return Product::create([
            'name' => 'T-shirt delete',
            'slug' => 't-shirt-delete',
            'sku' => 'PA-DELETE',
            'base_price_cents' => 1990,
            'estimated_delivery' => '7-10 giorni lavorativi',
            'is_active' => true,
        ]);
    }
}
