<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_lookup_order_by_number_and_email(): void
    {
        $order = $this->order();

        $this
            ->post(route('orders.lookup.show'), [
                'number' => $order->number,
                'email' => 'cliente@example.test',
            ])
            ->assertOk()
            ->assertSee($order->number)
            ->assertSee('PA-LOOKUP-SHIRT')
            ->assertSee('UPS 1Z999AA10123456784');
    }

    public function test_order_lookup_rejects_wrong_email(): void
    {
        $order = $this->order();

        $this
            ->from(route('orders.lookup'))
            ->post(route('orders.lookup.show'), [
                'number' => $order->number,
                'email' => 'wrong@example.test',
            ])
            ->assertRedirect(route('orders.lookup'))
            ->assertSessionHasErrors('number');
    }

    public function test_honeypot_blocks_order_lookup_submission(): void
    {
        $order = $this->order();

        $this
            ->from(route('orders.lookup'))
            ->post(route('orders.lookup.show'), [
                'website' => 'https://spam.example',
                'number' => $order->number,
                'email' => 'cliente@example.test',
            ])
            ->assertRedirect(route('orders.lookup'))
            ->assertSessionHasErrors('website');
    }

    private function order(): Order
    {
        $customer = Customer::create([
            'email' => 'cliente@example.test',
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);

        $product = Product::create([
            'name' => 'PA Lookup Shirt',
            'slug' => 'pa-lookup-shirt',
            'sku' => 'PA-LOOKUP-SHIRT',
            'base_price_cents' => 1990,
            'estimated_delivery' => '7-10 giorni lavorativi',
            'is_active' => true,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'number' => 'PA-LOOKUP-TEST',
            'status' => 'fulfilled',
            'payment_status' => 'paid',
            'subtotal_cents' => 1990,
            'shipping_cents' => 0,
            'total_cents' => 1990,
            'carrier' => 'ups',
            'tracking_number' => '1Z999AA10123456784',
            'tracking_url' => 'https://www.ups.com/track?tracknum=1Z999AA10123456784',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'variant_quantities' => [[
                'size' => 'M',
                'color' => 'Nero',
                'quantity' => 1,
            ]],
            'print_zones' => [[
                'name' => 'Retro',
            ]],
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
