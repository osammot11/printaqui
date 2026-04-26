<?php

namespace Tests\Feature;

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
}
