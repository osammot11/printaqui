<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_page_starts_with_prompt_when_query_is_empty(): void
    {
        $this->product('PA Hidden Search Tee', 'pa-hidden-search-tee', 'PA-HIDDEN-SEARCH', true);

        $this->get(route('search'))
            ->assertOk()
            ->assertSee('Inserisci una parola chiave')
            ->assertDontSee('PA Hidden Search Tee');
    }

    public function test_search_finds_active_products_by_name_sku_and_category(): void
    {
        $category = Category::create([
            'name' => 'Felpe',
            'slug' => 'felpe',
            'is_active' => true,
        ]);

        $this->product('PA Hoodie Premium', 'pa-hoodie-premium', 'PA-HOODIE-PREMIUM', true, $category->id);
        $this->product('PA Search SKU', 'pa-search-sku', 'PA-SPECIAL-123', true);
        $this->product('PA Inactive Hoodie', 'pa-inactive-hoodie', 'PA-INACTIVE-HOODIE', false, $category->id);

        $this->get(route('search', ['q' => 'felpe']))
            ->assertOk()
            ->assertSee('PA Hoodie Premium')
            ->assertDontSee('PA Inactive Hoodie');

        $this->get(route('search', ['q' => 'special-123']))
            ->assertOk()
            ->assertSee('PA Search SKU');
    }

    public function test_search_can_sort_results_by_price(): void
    {
        $cheap = $this->product('PA Search Cheap', 'pa-search-cheap', 'PA-SEARCH-CHEAP', true, null, 1500);
        $expensive = $this->product('PA Search Expensive', 'pa-search-expensive', 'PA-SEARCH-EXPENSIVE', true, null, 4500);

        $response = $this->get(route('search', [
            'q' => 'search',
            'sort' => 'price_asc',
        ]));

        $response->assertOk();
        $response->assertSeeInOrder([$cheap->name, $expensive->name]);
    }

    private function product(
        string $name,
        string $slug,
        string $sku,
        bool $active,
        ?int $categoryId = null,
        int $priceCents = 2490
    ): Product {
        return Product::create([
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => $slug,
            'sku' => $sku,
            'base_price_cents' => $priceCents,
            'estimated_delivery' => '7-10 giorni lavorativi',
            'is_active' => $active,
        ]);
    }
}
