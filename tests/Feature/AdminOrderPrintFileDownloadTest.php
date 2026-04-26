<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminOrderPrintFileDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_order_print_file_with_position_filename(): void
    {
        Config::set('filesystems.default', 'local');
        Storage::fake('local');

        $admin = User::factory()->create(['is_admin' => true]);
        [$order, $printFile] = $this->orderWithPrintFile();

        Storage::disk('local')->put($printFile->stored_path, 'print-file-content');

        $response = $this->actingAs($admin)
            ->get(route('admin.orders.print-files.download', [$order, $printFile]));

        $response->assertOk();
        $response->assertHeader(
            'content-disposition',
            'attachment; filename=pa-download-test_pa-download_fronte-cuore.png'
        );
    }

    public function test_admin_cannot_download_file_from_another_order(): void
    {
        Config::set('filesystems.default', 'local');
        Storage::fake('local');

        $admin = User::factory()->create(['is_admin' => true]);
        [, $printFile] = $this->orderWithPrintFile();
        $otherOrder = Order::create([
            'number' => 'PA-OTHER-ORDER',
            'status' => 'unfulfilled',
            'payment_status' => 'paid',
            'subtotal_cents' => 1000,
            'shipping_cents' => 0,
            'total_cents' => 1000,
        ]);

        Storage::disk('local')->put($printFile->stored_path, 'print-file-content');

        $this->actingAs($admin)
            ->get(route('admin.orders.print-files.download', [$otherOrder, $printFile]))
            ->assertNotFound();
    }

    private function orderWithPrintFile(): array
    {
        $product = Product::create([
            'name' => 'T-shirt download',
            'slug' => 't-shirt-download',
            'sku' => 'PA-DOWNLOAD',
            'base_price_cents' => 1990,
            'estimated_delivery' => '7-10 giorni lavorativi',
            'is_active' => true,
        ]);

        $order = Order::create([
            'number' => 'PA-DOWNLOAD-TEST',
            'status' => 'unfulfilled',
            'payment_status' => 'paid',
            'subtotal_cents' => 1990,
            'shipping_cents' => 0,
            'total_cents' => 1990,
        ]);

        $item = $order->items()->create([
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

        $printFile = $item->printFiles()->create([
            'print_zone_id' => null,
            'zone_name' => 'Fronte cuore',
            'original_name' => 'logo.png',
            'stored_path' => 'cart-print-files/fronte-cuore.png',
            'mime_type' => 'image/png',
            'size_bytes' => 18,
        ]);

        return [$order, $printFile];
    }
}
