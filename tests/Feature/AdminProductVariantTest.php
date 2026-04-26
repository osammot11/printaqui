<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductVariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_create_product_with_duplicate_variant_skus(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'name' => 'T-shirt duplicate',
                'sku' => 'PA-DUPLICATE',
                'base_price' => 19.90,
                'estimated_delivery' => '7-10 giorni lavorativi',
                'is_active' => '1',
                'variants' => [
                    ['size' => 'S', 'color' => 'Nero', 'stock' => 10, 'sku' => 'PA-DUP-S'],
                    ['size' => 'M', 'color' => 'Nero', 'stock' => 10, 'sku' => 'PA-DUP-S'],
                ],
            ])
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors('variants');

        $this->assertDatabaseMissing('products', ['sku' => 'PA-DUPLICATE']);
    }

    public function test_admin_can_save_inactive_variant(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'name' => 'T-shirt variants',
                'sku' => 'PA-VARIANTS',
                'base_price' => 19.90,
                'estimated_delivery' => '7-10 giorni lavorativi',
                'is_active' => '1',
                'variants' => [
                    ['size' => 'S', 'color' => 'Nero', 'stock' => 10, 'sku' => 'PA-VARIANTS-S'],
                ],
            ])
            ->assertRedirect();

        $product = Product::where('sku', 'PA-VARIANTS')->firstOrFail();

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'PA-VARIANTS-S',
            'is_active' => false,
        ]);
    }
}
