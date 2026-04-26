<?php

namespace Tests\Feature;

use App\Models\DiscountCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDiscountCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_percent_discount_code(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.discounts.store'), [
                'code' => 'welcome15',
                'type' => 'percent',
                'value' => 15,
                'is_active' => '1',
                'usage_limit' => 100,
            ])
            ->assertRedirect(route('admin.discounts.index'));

        $this->assertDatabaseHas('discount_codes', [
            'code' => 'WELCOME15',
            'type' => 'percent',
            'value' => 15,
            'usage_limit' => 100,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_fixed_discount_code_in_euros(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.discounts.store'), [
                'code' => 'FIVE',
                'type' => 'fixed',
                'value' => 5.50,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.discounts.index'));

        $this->assertDatabaseHas('discount_codes', [
            'code' => 'FIVE',
            'type' => 'fixed',
            'value' => 550,
        ]);
    }

    public function test_admin_can_deactivate_discount_code(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $discount = DiscountCode::create([
            'code' => 'ACTIVE10',
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.discounts.destroy', $discount))
            ->assertRedirect(route('admin.discounts.index'));

        $this->assertFalse($discount->refresh()->is_active);
    }

    public function test_admin_discount_form_rejects_duplicate_codes_after_normalizing(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        DiscountCode::create([
            'code' => 'WELCOME10',
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.discounts.create'))
            ->post(route('admin.discounts.store'), [
                'code' => 'welcome10',
                'type' => 'percent',
                'value' => 10,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.discounts.create'))
            ->assertSessionHasErrors('code');
    }
}
