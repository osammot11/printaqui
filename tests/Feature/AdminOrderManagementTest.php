<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Customer;
use App\Models\User;
use App\Services\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
use RuntimeException;
use Stripe\Refund;
use Tests\TestCase;

class AdminOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_order_internal_details(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $order = $this->order();

        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $order), [
                'status' => 'in_production',
                'internal_notes' => 'Verificare file prima della stampa.',
                'tags' => 'bulk, Urgente, bulk, verifica file',
                'carrier' => 'dhl',
                'tracking_number' => 'JD014600006999999999',
            ])
            ->assertRedirect();

        $order->refresh();

        $this->assertSame('in_production', $order->status);
        $this->assertNull($order->fulfilled_at);
        $this->assertSame('Verificare file prima della stampa.', $order->internal_notes);
        $this->assertSame(['bulk', 'Urgente', 'verifica file'], $order->tags);
        $this->assertSame('dhl', $order->carrier);
        $this->assertSame('JD014600006999999999', $order->tracking_number);
        $this->assertStringContainsString('tracking-id=JD014600006999999999', $order->tracking_url);
    }

    public function test_admin_order_carrier_must_be_supported(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $order = $this->order();

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.update', $order), [
                'status' => 'unfulfilled',
                'carrier' => 'unsupported',
                'tracking_number' => '123',
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasErrors('carrier');
    }

    public function test_admin_tracking_update_sends_brevo_email_once(): void
    {
        Config::set('services.brevo.api_key', 'test-brevo-key');
        Config::set('services.brevo.sender_email', 'shop@example.test');
        Config::set('services.brevo.sender_name', 'Printaqui');
        Config::set('services.brevo.tracking_update_template_id', 456);

        Http::fake([
            'api.brevo.com/v3/smtp/email' => Http::response(['messageId' => 'brevo-tracking-message'], 201),
        ]);

        $admin = User::factory()->create(['is_admin' => true]);
        $customer = Customer::create([
            'email' => 'cliente@example.test',
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);
        $order = $this->order(['customer_id' => $customer->id]);

        $payload = [
            'status' => 'unfulfilled',
            'carrier' => 'ups',
            'tracking_number' => '1Z999AA10123456784',
        ];

        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $order), $payload)
            ->assertRedirect();

        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $order->refresh()), $payload)
            ->assertRedirect();

        $order->refresh();

        $this->assertNotNull($order->tracking_notification_sent_at);
        $this->assertNull($order->tracking_notification_failed_at);
        $this->assertStringContainsString('tracknum=1Z999AA10123456784', $order->tracking_url);

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.brevo.com/v3/smtp/email'
            && $request['templateId'] === 456
            && $request['to'][0]['email'] === 'cliente@example.test'
            && $request['params']['carrier'] === 'UPS'
            && $request['params']['tracking_number'] === '1Z999AA10123456784'
            && $request['params']['tracking_url'] === $order->tracking_url);
    }

    public function test_admin_can_set_operational_order_statuses(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $order = $this->order();

        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $order), [
                'status' => 'waiting_files',
                'internal_notes' => 'Serve file vettoriale.',
                'tags' => 'file mancanti',
            ])
            ->assertRedirect();

        $order->refresh();

        $this->assertSame('waiting_files', $order->status);
        $this->assertSame('In attesa file', $order->statusLabel());
        $this->assertNull($order->fulfilled_at);

        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $order), [
                'status' => 'fulfilled',
                'internal_notes' => 'Produzione completata.',
            ])
            ->assertRedirect();

        $order->refresh();

        $this->assertSame('fulfilled', $order->status);
        $this->assertNotNull($order->fulfilled_at);
    }

    public function test_admin_order_page_shows_checkout_customer_data(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = Customer::create([
            'email' => 'cliente@example.test',
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'phone' => '+39 333 1234567',
            'shipping_address' => [
                'address' => 'Via Roma 1',
                'city' => 'Milano',
                'postal_code' => '20100',
                'country' => 'IT',
            ],
            'billing_address' => [
                'address' => 'Via Fatture 2',
                'city' => 'Torino',
                'postal_code' => '10100',
                'country' => 'IT',
            ],
        ]);
        $order = $this->order([
            'customer_id' => $customer->id,
            'shipping_address' => [
                'address' => 'Via Roma 1',
                'city' => 'Milano',
                'postal_code' => '20100',
                'country' => 'IT',
            ],
            'billing_address' => [
                'address' => 'Via Fatture 2',
                'city' => 'Torino',
                'postal_code' => '10100',
                'country' => 'IT',
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Dati checkout')
            ->assertSee('Mario Rossi')
            ->assertSee('cliente@example.test')
            ->assertSee('+39 333 1234567')
            ->assertSee('Via Roma 1')
            ->assertSee('20100 Milano')
            ->assertSee('Via Fatture 2')
            ->assertSee('10100 Torino');
    }

    public function test_admin_can_filter_orders(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $mario = Customer::create([
            'email' => 'mario@example.test',
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);
        $luca = Customer::create([
            'email' => 'luca@example.test',
            'first_name' => 'Luca',
            'last_name' => 'Bianchi',
        ]);

        $matchingOrder = $this->order([
            'customer_id' => $mario->id,
            'number' => 'PA-FILTER-MARIO',
            'status' => 'in_production',
            'payment_status' => 'paid',
            'tracking_number' => 'TRACK-MARIO',
        ]);
        $this->order([
            'customer_id' => $luca->id,
            'number' => 'PA-FILTER-LUCA',
            'status' => 'fulfilled',
            'payment_status' => 'refunded',
            'tracking_number' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index', [
                'q' => 'mario@example.test',
                'status' => 'in_production',
                'payment_status' => 'paid',
                'tracking' => 'with',
            ]))
            ->assertOk()
            ->assertSee($matchingOrder->number)
            ->assertSee('In produzione')
            ->assertSee('Pagato')
            ->assertDontSee('PA-FILTER-LUCA');
    }

    public function test_admin_can_export_filtered_orders_csv(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = Customer::create([
            'email' => 'export@example.test',
            'first_name' => 'Export',
            'last_name' => 'Cliente',
        ]);

        $matchingOrder = $this->order([
            'customer_id' => $customer->id,
            'number' => 'PA-EXPORT-PAID',
            'status' => 'waiting_files',
            'payment_status' => 'paid',
            'total_cents' => 2490,
            'tracking_number' => null,
            'shipping_address' => ['country' => 'IT'],
            'tags' => ['bulk'],
        ]);
        $this->order([
            'number' => 'PA-EXPORT-PENDING',
            'status' => 'unfulfilled',
            'payment_status' => 'pending',
            'total_cents' => 1000,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.orders.export', [
                'payment_status' => 'paid',
                'tracking' => 'missing',
            ]));

        $response
            ->assertOk()
            ->assertDownload();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('numero_ordine', $csv);
        $this->assertStringContainsString($matchingOrder->number, $csv);
        $this->assertStringContainsString('In attesa file', $csv);
        $this->assertStringContainsString('export@example.test', $csv);
        $this->assertStringNotContainsString('PA-EXPORT-PENDING', $csv);
    }

    public function test_admin_can_refund_paid_stripe_order(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $order = $this->order([
            'stripe_payment_intent_id' => 'pi_admin_refund_test',
            'total_cents' => 3490,
        ]);

        $this->mock(StripeCheckoutService::class, function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('refundOrder')
                ->once()
                ->withArgs(fn (Order $refundedOrder, ?string $reason) => $refundedOrder->is($order)
                    && $reason === 'Richiesta cliente')
                ->andReturnUsing(function (Order $refundedOrder) {
                    $refundedOrder->update([
                        'payment_status' => 'refunded',
                        'stripe_refund_id' => 're_admin_refund_test',
                        'refunded_cents' => $refundedOrder->total_cents,
                        'refunded_at' => now(),
                    ]);

                    return Refund::constructFrom(['id' => 're_admin_refund_test']);
                });
        });

        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $order), [
                'refund_reason' => 'Richiesta cliente',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Ordine rimborsato su Stripe.');

        $order->refresh();

        $this->assertSame('refunded', $order->payment_status);
        $this->assertSame('re_admin_refund_test', $order->stripe_refund_id);
        $this->assertSame(3490, $order->refunded_cents);
        $this->assertNotNull($order->refunded_at);
    }

    public function test_admin_refund_errors_are_shown_without_updating_order(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $order = $this->order([
            'payment_status' => 'pending',
            'stripe_payment_intent_id' => null,
        ]);

        $this->mock(StripeCheckoutService::class, function (MockInterface $mock) {
            $mock->shouldReceive('refundOrder')
                ->once()
                ->andThrow(new RuntimeException('Puoi rimborsare solo ordini pagati.'));
        });

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.refund', $order), [
                'refund_reason' => 'Tentativo test',
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasErrors('refund');

        $order->refresh();

        $this->assertSame('pending', $order->payment_status);
        $this->assertNull($order->stripe_refund_id);
        $this->assertNull($order->refunded_at);
    }

    private function order(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'number' => 'PA-ORDER-MGMT',
            'status' => 'unfulfilled',
            'payment_status' => 'paid',
            'subtotal_cents' => 1000,
            'shipping_cents' => 0,
            'total_cents' => 1000,
        ], $overrides));
    }
}
