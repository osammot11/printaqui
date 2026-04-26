<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_calculates_net_sales_refunds_and_aov(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = Customer::create([
            'email' => 'cliente@example.test',
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);

        $this->order($customer->id, 'PA-PAID-1', 'paid', 10000, 0, 'unfulfilled');
        $this->order($customer->id, 'PA-PAID-2', 'paid', 5000, 0, 'fulfilled');
        $this->order($customer->id, 'PA-REFUND-1', 'refunded', 4000, 4000, 'unfulfilled');
        $this->order($customer->id, 'PA-PENDING-1', 'pending', 9999, 0, 'unfulfilled');
        $this->order($customer->id, 'PA-FAILED-1', 'failed', 3000, 0, 'unfulfilled');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Net sales')
            ->assertSee('€ 150,00')
            ->assertSee('Gross sales')
            ->assertSee('€ 190,00')
            ->assertSee('Rimborsi')
            ->assertSee('€ 40,00')
            ->assertSee('AOV')
            ->assertSee('€ 50,00')
            ->assertSee('Ordini revenue')
            ->assertSee('Da evadere')
            ->assertSee('Pagamenti pending')
            ->assertSee('PA-PENDING-1');
    }

    private function order(
        int $customerId,
        string $number,
        string $paymentStatus,
        int $totalCents,
        int $refundedCents,
        string $status
    ): Order {
        return Order::create([
            'customer_id' => $customerId,
            'number' => $number,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'subtotal_cents' => $totalCents,
            'shipping_cents' => 0,
            'total_cents' => $totalCents,
            'refunded_cents' => $refundedCents,
            'refunded_at' => $refundedCents > 0 ? now() : null,
        ]);
    }
}
