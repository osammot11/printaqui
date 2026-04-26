<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontCollectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_collections_index_shows_active_categories_with_active_product_counts(): void
    {
        $activeCategory = Category::create([
            'name' => 'T-shirt',
            'slug' => 't-shirt',
            'description' => 'Maglie personalizzabili',
            'is_active' => true,
        ]);
        $inactiveCategory = Category::create([
            'name' => 'Archivio',
            'slug' => 'archivio',
            'is_active' => false,
        ]);

        $this->product($activeCategory, 'PA Active Tee', 'pa-active-tee', true);
        $this->product($activeCategory, 'PA Hidden Tee', 'pa-hidden-tee', false);
        $this->product($inactiveCategory, 'PA Archive Tee', 'pa-archive-tee', true);

        $this->get(route('collections.index'))
            ->assertOk()
            ->assertSee('Collezioni')
            ->assertSee('T-shirt')
            ->assertSee('Maglie personalizzabili')
            ->assertSee('1 prodotto')
            ->assertSee(route('collections.show', $activeCategory->slug), false)
            ->assertDontSee('Archivio');
    }

    public function test_shop_index_keeps_showing_all_active_products(): void
    {
        $category = Category::create([
            'name' => 'Felpe',
            'slug' => 'felpe',
            'is_active' => true,
        ]);
        $activeProduct = $this->product($category, 'PA Hoodie', 'pa-hoodie', true);
        $this->product($category, 'PA Hidden Hoodie', 'pa-hidden-hoodie', false);

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertSee('Shop')
            ->assertSee($activeProduct->name)
            ->assertDontSee('PA Hidden Hoodie');
    }

    private function product(Category $category, string $name, string $slug, bool $active): Product
    {
        return Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => $slug,
            'sku' => strtoupper(str_replace('-', '-', $slug)),
            'base_price_cents' => 2490,
            'estimated_delivery' => '7-10 giorni lavorativi',
            'is_active' => $active,
        ]);
    }
}
