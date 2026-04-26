<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_user_can_access_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_non_admin_user_cannot_access_dashboard(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_can_login(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.test',
            'password' => 'secret-password',
            'is_admin' => true,
        ]);

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_honeypot_blocks_admin_login_submission(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.test',
            'password' => 'secret-password',
            'is_admin' => true,
        ]);

        $this
            ->from(route('admin.login'))
            ->post(route('admin.login.store'), [
                'website' => 'https://spam.example',
                'email' => $admin->email,
                'password' => 'secret-password',
            ])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('website');

        $this->assertGuest();
    }

    public function test_admin_login_is_rate_limited(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('admin.login.store'), [
                'email' => 'blocked@example.test',
                'password' => 'wrong-password',
            ]);
        }

        $this
            ->post(route('admin.login.store'), [
                'email' => 'blocked@example.test',
                'password' => 'wrong-password',
            ])
            ->assertTooManyRequests();
    }
}
