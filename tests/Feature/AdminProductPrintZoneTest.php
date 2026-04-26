<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductPrintZoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_product_with_custom_print_zones(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'name' => 'T-shirt zone',
                'sku' => 'PA-ZONES',
                'base_price' => 19.90,
                'estimated_delivery' => '7-10 giorni lavorativi',
                'is_active' => '1',
                'print_zones' => [
                    ['name' => 'Fronte cuore', 'price' => 6.50, 'is_active' => '1'],
                    ['name' => 'Retro grande', 'price' => 9],
                ],
            ])
            ->assertRedirect();

        $product = Product::where('sku', 'PA-ZONES')->firstOrFail();

        $this->assertDatabaseHas('print_zones', [
            'product_id' => $product->id,
            'name' => 'Fronte cuore',
            'slug' => 'fronte-cuore',
            'additional_price_cents' => 650,
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('print_zones', [
            'product_id' => $product->id,
            'name' => 'Retro grande',
            'slug' => 'retro-grande',
            'additional_price_cents' => 900,
            'is_active' => false,
            'sort_order' => 1,
        ]);
    }

    public function test_admin_cannot_create_product_with_duplicate_print_zones(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'name' => 'T-shirt duplicate zones',
                'sku' => 'PA-DUP-ZONES',
                'base_price' => 19.90,
                'estimated_delivery' => '7-10 giorni lavorativi',
                'is_active' => '1',
                'print_zones' => [
                    ['name' => 'Retro grande', 'price' => 9, 'is_active' => '1'],
                    ['name' => 'Retro grande', 'price' => 12, 'is_active' => '1'],
                ],
            ])
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors('print_zones');

        $this->assertDatabaseMissing('products', ['sku' => 'PA-DUP-ZONES']);
    }
}
