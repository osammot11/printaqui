<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Stripe\PaymentIntent;
use Tests\TestCase;

class BrevoOrderConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_order_sends_brevo_confirmation_once(): void
    {
        Config::set('services.brevo.api_key', 'test-brevo-key');
        Config::set('services.brevo.sender_email', 'shop@example.test');
        Config::set('services.brevo.sender_name', 'Printaqui');
        Config::set('services.brevo.order_confirmation_template_id', 123);

        Http::fake([
            'api.brevo.com/v3/smtp/email' => Http::response(['messageId' => 'brevo-message'], 201),
        ]);

        $order = $this->paidOrder();
        $paymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_brevo_test',
            'status' => 'succeeded',
            'metadata' => [
                'order_id' => (string) $order->id,
            ],
        ]);

        $service = app(StripeCheckoutService::class);

        $service->syncOrderFromPaymentIntent($paymentIntent);
        $service->syncOrderFromPaymentIntent($paymentIntent);

        $order->refresh();

        $this->assertNotNull($order->order_confirmation_sent_at);
        $this->assertNull($order->order_confirmation_failed_at);

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.brevo.com/v3/smtp/email'
            && $request->hasHeader('api-key', 'test-brevo-key')
            && $request['templateId'] === 123
            && $request['to'][0]['email'] === 'cliente@example.test'
            && $request['params']['order_number'] === $order->number);
    }

    public function test_unconfigured_brevo_does_not_block_paid_order(): void
    {
        Config::set('services.brevo.api_key', null);
        Config::set('services.brevo.sender_email', null);

        Http::fake();

        $order = $this->paidOrder();
        $paymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_brevo_unconfigured',
            'status' => 'succeeded',
            'metadata' => [
                'order_id' => (string) $order->id,
            ],
        ]);

        app(StripeCheckoutService::class)->syncOrderFromPaymentIntent($paymentIntent);

        $order->refresh();

        $this->assertSame('paid', $order->payment_status);
        $this->assertNull($order->order_confirmation_sent_at);
        $this->assertNull($order->order_confirmation_failed_at);
        Http::assertNothingSent();
    }

    private function paidOrder(): Order
    {
        $customer = Customer::create([
            'email' => 'cliente@example.test',
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);

        $product = Product::create([
            'name' => 'T-shirt Brevo',
            'slug' => 't-shirt-brevo',
            'sku' => 'PA-BREVO',
            'base_price_cents' => 1990,
            'estimated_delivery' => '7-10 giorni lavorativi',
            'is_active' => true,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'number' => 'PA-BREVO-TEST',
            'status' => 'unfulfilled',
            'payment_status' => 'pending',
            'stripe_payment_intent_id' => 'pi_brevo_test',
            'subtotal_cents' => 1990,
            'shipping_cents' => 0,
            'total_cents' => 1990,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'variant_quantities' => [],
            'print_zones' => [],
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'base_unit_price_cents' => 1990,
            'print_unit_price_cents' => 0,
            'quantity' => 1,
            'line_total_cents' => 1990,
        ]);

        return $order;
    }
}
