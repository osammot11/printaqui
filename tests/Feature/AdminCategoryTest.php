<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_category_with_normalized_slug(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Felpe Personalizzate',
                'description' => 'Collezione hoodie e zip.',
                'sort_order' => 20,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Felpe Personalizzate',
            'slug' => 'felpe-personalizzate',
            'sort_order' => 20,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_and_deactivate_category(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::create([
            'name' => 'T-shirt',
            'slug' => 't-shirt',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.categories.update', $category), [
                'name' => 'T-shirt DTF',
                'slug' => 'T Shirt DTF',
                'sort_order' => 5,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.categories.edit', $category));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'T-shirt DTF',
            'slug' => 't-shirt-dtf',
            'sort_order' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertFalse($category->refresh()->is_active);
    }

    public function test_admin_category_form_rejects_duplicate_slugs_after_normalizing(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Category::create([
            'name' => 'T-shirt',
            'slug' => 't-shirt-personalizzate',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.categories.create'))
            ->post(route('admin.categories.store'), [
                'name' => 'T-shirt personalizzate',
                'slug' => 'T shirt personalizzate',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.categories.create'))
            ->assertSessionHasErrors('slug');
    }

    public function test_inactive_categories_are_not_publicly_browsable(): void
    {
        $category = Category::create([
            'name' => 'Archivio',
            'slug' => 'archivio',
            'is_active' => false,
        ]);

        $this->get(route('collections.show', $category->slug))->assertNotFound();
    }
}
