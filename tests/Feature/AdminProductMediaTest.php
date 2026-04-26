<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_product_with_media_and_size_chart(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'name' => 'T-shirt media test',
                'sku' => 'PA-MEDIA-TEST',
                'base_price' => 19.90,
                'estimated_delivery' => '7-10 giorni lavorativi',
                'is_active' => '1',
                'media_images' => [
                    UploadedFile::fake()->image('front.jpg', 900, 900),
                ],
                'size_chart_file' => UploadedFile::fake()->image('size-chart.png', 900, 1200),
            ])
            ->assertRedirect();

        $product = Product::where('sku', 'PA-MEDIA-TEST')->firstOrFail();

        $this->assertCount(1, $product->mediaItems());
        $this->assertTrue($product->mediaItems()[0]['is_primary']);
        $this->assertStringStartsWith('/storage/', $product->primaryMediaUrl());
        $this->assertStringStartsWith('/storage/', $product->sizeChartUrl());
        $this->assertNotNull($product->sizeChartUrl());

        Storage::disk('public')->assertExists($product->mediaItems()[0]['path']);
        Storage::disk('public')->assertExists($product->size_chart['path']);
    }
}
