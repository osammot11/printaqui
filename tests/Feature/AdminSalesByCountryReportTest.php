<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSalesByCountryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_daily_sales_by_country_report(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->order([
            'number' => 'PA-REPORT-IT',
            'total_cents' => 4568,
            'shipping_address' => ['country' => 'IT'],
        ], '2026-04-26 10:00:00');
        $this->order([
            'number' => 'PA-REPORT-DE',
            'total_cents' => 6423,
            'shipping_address' => ['country' => 'DE'],
        ], '2026-04-26 14:00:00');
        $this->order([
            'number' => 'PA-REPORT-PENDING',
            'payment_status' => 'pending',
            'total_cents' => 9999,
            'shipping_address' => ['country' => 'FR'],
        ], '2026-04-26 15:00:00');

        $this->actingAs($admin)
            ->get(route('admin.reports.sales-by-country', [
                'date_from' => '2026-04-26',
                'date_to' => '2026-04-27',
                'metric' => 'gross',
            ]))
            ->assertOk()
            ->assertSee('Report fatturato per paese')
            ->assertSee('Germania')
            ->assertSee('Italia')
            ->assertSee('€ 64,23')
            ->assertSee('€ 45,68')
            ->assertSee('€ 109,91')
            ->assertDontSee('Francia');
    }

    public function test_admin_can_export_daily_sales_by_country_csv(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->order([
            'number' => 'PA-CSV-IT',
            'total_cents' => 4568,
            'shipping_address' => ['country' => 'IT'],
        ], '2026-04-26 10:00:00');
        $this->order([
            'number' => 'PA-CSV-ES',
            'total_cents' => 3200,
            'shipping_address' => ['country' => 'ES'],
        ], '2026-04-27 10:00:00');

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.sales-by-country.export', [
                'date_from' => '2026-04-26',
                'date_to' => '2026-04-27',
                'metric' => 'gross',
            ]));

        $response
            ->assertOk()
            ->assertDownload();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('data;Italia;Spagna;totale_giorno', $csv);
        $this->assertStringContainsString('2026-04-26;45,68;0,00;45,68', $csv);
        $this->assertStringContainsString('2026-04-27;0,00;32,00;32,00', $csv);
        $this->assertStringContainsString('totale_periodo;45,68;32,00;77,68', $csv);
    }

    public function test_report_can_use_net_sales_after_refunds(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->order([
            'number' => 'PA-NET-REFUND',
            'payment_status' => 'refunded',
            'total_cents' => 10000,
            'refunded_cents' => 2500,
            'shipping_address' => ['country' => 'FR'],
        ], '2026-04-26 10:00:00');

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.sales-by-country.export', [
                'date_from' => '2026-04-26',
                'date_to' => '2026-04-26',
                'metric' => 'net',
            ]));

        $csv = $response->streamedContent();

        $this->assertStringContainsString('data;Francia;totale_giorno', $csv);
        $this->assertStringContainsString('2026-04-26;75,00;75,00', $csv);
    }

    public function test_report_groups_by_paid_at_instead_of_created_at(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->order([
            'number' => 'PA-PAID-LATER',
            'total_cents' => 1200,
            'shipping_address' => ['country' => 'NL'],
        ], '2026-04-25 23:50:00', '2026-04-26 00:10:00');

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.sales-by-country.export', [
                'date_from' => '2026-04-26',
                'date_to' => '2026-04-26',
                'metric' => 'gross',
            ]));

        $csv = $response->streamedContent();

        $this->assertStringContainsString('data;"Paesi Bassi";totale_giorno', $csv);
        $this->assertStringContainsString('2026-04-26;12,00;12,00', $csv);
    }

    private function order(array $overrides, string $createdAt, ?string $paidAt = null): Order
    {
        $customer = Customer::create([
            'email' => strtolower($overrides['number']).'@example.test',
            'first_name' => 'Cliente',
            'last_name' => 'Report',
        ]);

        $order = Order::create(array_merge([
            'customer_id' => $customer->id,
            'number' => 'PA-REPORT',
            'status' => 'fulfilled',
            'payment_status' => 'paid',
            'subtotal_cents' => $overrides['total_cents'] ?? 1000,
            'shipping_cents' => 0,
            'total_cents' => 1000,
            'refunded_cents' => 0,
            'shipping_address' => ['country' => 'IT'],
            'paid_at' => in_array($overrides['payment_status'] ?? 'paid', ['paid', 'refunded'], true) ? ($paidAt ?? $createdAt) : null,
        ], $overrides));

        $order->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $order;
    }
}
