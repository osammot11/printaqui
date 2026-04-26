<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\DiscountCode;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Stripe\PaymentIntent;
use Tests\TestCase;

class StripeStockSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_stripe_payment_decrements_stock_once(): void
    {
        [$order, $variant] = $this->orderWithVariantQuantity(3);

        $paymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_stock_test',
            'status' => 'succeeded',
            'metadata' => [
                'order_id' => (string) $order->id,
            ],
        ]);

        $service = app(StripeCheckoutService::class);

        Carbon::setTestNow('2026-04-27 09:30:00');
        $service->syncOrderFromPaymentIntent($paymentIntent);
        Carbon::setTestNow('2026-04-28 09:30:00');
        $service->syncOrderFromPaymentIntent($paymentIntent);
        Carbon::setTestNow();

        $order->refresh();
        $variant->refresh();

        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('2026-04-27 09:30:00', $order->paid_at->format('Y-m-d H:i:s'));
        $this->assertNotNull($order->stock_decremented_at);
        $this->assertSame(7, $variant->stock);
    }

    public function test_failed_stripe_payment_does_not_decrement_stock(): void
    {
        [$order, $variant] = $this->orderWithVariantQuantity(3);

        $paymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_stock_failed',
            'status' => 'requires_payment_method',
            'metadata' => [
                'order_id' => (string) $order->id,
            ],
        ]);

        app(StripeCheckoutService::class)->syncOrderFromPaymentIntent($paymentIntent);

        $order->refresh();
        $variant->refresh();

        $this->assertSame('failed', $order->payment_status);
        $this->assertNull($order->stock_decremented_at);
        $this->assertSame(10, $variant->stock);
    }

    public function test_paid_stripe_payment_records_discount_usage_once(): void
    {
        [$order] = $this->orderWithVariantQuantity(1);
        $discount = DiscountCode::create([
            'code' => 'WELCOME10',
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
        ]);
        $order->update([
            'discount_code_id' => $discount->id,
            'discount_code' => $discount->code,
            'discount_cents' => 199,
        ]);

        $paymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_stock_test',
            'status' => 'succeeded',
            'metadata' => [
                'order_id' => (string) $order->id,
            ],
        ]);

        $service = app(StripeCheckoutService::class);

        $service->syncOrderFromPaymentIntent($paymentIntent);
        $service->syncOrderFromPaymentIntent($paymentIntent);

        $order->refresh();
        $discount->refresh();

        $this->assertNotNull($order->discount_usage_recorded_at);
        $this->assertSame(1, $discount->used_count);
    }

    private function orderWithVariantQuantity(int $quantity): array
    {
        $product = Product::create([
            'name' => 'T-shirt Stripe stock',
            'slug' => 't-shirt-stripe-stock',
            'sku' => 'PA-STRIPE-STOCK',
            'base_price_cents' => 1990,
            'estimated_delivery' => '7-10 giorni lavorativi',
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PA-STRIPE-STOCK-S',
            'size' => 'S',
            'color' => 'Nero',
            'stock' => 10,
            'is_active' => true,
        ]);

        $order = Order::create([
            'number' => 'PA-TEST-STOCK',
            'status' => 'unfulfilled',
            'payment_status' => 'pending',
            'stripe_payment_intent_id' => 'pi_stock_test',
            'subtotal_cents' => 1990 * $quantity,
            'shipping_cents' => 0,
            'total_cents' => 1990 * $quantity,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'variant_quantities' => [[
                'variant_id' => $variant->id,
                'sku' => $variant->sku,
                'size' => $variant->size,
                'color' => $variant->color,
                'quantity' => $quantity,
            ]],
            'print_zones' => [],
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'base_unit_price_cents' => 1990,
            'print_unit_price_cents' => 0,
            'quantity' => $quantity,
            'line_total_cents' => 1990 * $quantity,
        ]);

        return [$order, $variant];
    }
}
