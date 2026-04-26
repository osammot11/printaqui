<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\DiscountCode;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        $apparel = Category::updateOrCreate(
            ['slug' => 'apparel'],
            ['name' => 'Apparel', 'description' => 'T-shirt, felpe e capi blank personalizzabili.', 'sort_order' => 1]
        );

        $accessories = Category::updateOrCreate(
            ['slug' => 'accessori'],
            ['name' => 'Accessori', 'description' => 'Tote e prodotti leggeri per stampa DTF.', 'sort_order' => 2]
        );

        $products = [
            [
                'category' => $apparel,
                'name' => 'T-shirt heavyweight unisex',
                'sku' => 'PA-TS-HW',
                'base_price_cents' => 1490,
                'internal_cost_cents' => 620,
                'media' => ['https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=1100&q=80'],
                'description' => 'T-shirt blank in cotone pesante, pronta per stampa DTF fronte e retro.',
            ],
            [
                'category' => $apparel,
                'name' => 'Hoodie premium',
                'sku' => 'PA-HD-PR',
                'base_price_cents' => 3490,
                'internal_cost_cents' => 1680,
                'media' => ['https://images.unsplash.com/photo-1556821840-3a63f95609a7?auto=format&fit=crop&w=1100&q=80'],
                'description' => 'Felpa premium con cappuccio, ideale per drop personalizzati e bulk.',
            ],
            [
                'category' => $accessories,
                'name' => 'Tote bag canvas',
                'sku' => 'PA-TB-CV',
                'base_price_cents' => 990,
                'internal_cost_cents' => 390,
                'media' => ['https://images.unsplash.com/photo-1597484662317-9bd7bdda2907?auto=format&fit=crop&w=1100&q=80'],
                'description' => 'Tote bag canvas blank con area stampa centrale.',
            ],
        ];

        foreach ($products as $data) {
            $product = Product::updateOrCreate(
                ['sku' => $data['sku']],
                [
                    'category_id' => $data['category']->id,
                    'name' => $data['name'],
                    'slug' => Str::slug($data['name']).'-'.Str::lower($data['sku']),
                    'description' => $data['description'],
                    'base_price_cents' => $data['base_price_cents'],
                    'internal_cost_cents' => $data['internal_cost_cents'],
                    'media' => $data['media'],
                    'estimated_delivery' => '7-10 giorni lavorativi',
                    'is_active' => true,
                ]
            );

            $product->variants()->delete();
            foreach (['S', 'M', 'L', 'XL'] as $size) {
                foreach ([['Nero', '#111111'], ['Bianco', '#f8f8f8']] as [$color, $hex]) {
                    $product->variants()->create([
                        'sku' => $data['sku'].'-'.$size.'-'.Str::upper(Str::substr($color, 0, 1)),
                        'size' => $size,
                        'color' => $color,
                        'hex_color' => $hex,
                        'stock' => 250,
                        'is_active' => true,
                    ]);
                }
            }

            $product->printZones()->delete();
            foreach ([
                ['Fronte cuore', 'fronte-cuore', 600, 1],
                ['Fronte grande', 'fronte-grande', 850, 2],
                ['Retro', 'retro', 900, 3],
                ['Manica', 'manica', 400, 4],
            ] as [$name, $slug, $price, $sort]) {
                $product->printZones()->create([
                    'name' => $name,
                    'slug' => $slug,
                    'additional_price_cents' => $price,
                    'sort_order' => $sort,
                    'is_active' => true,
                ]);
            }
        }

        ShippingRate::updateOrCreate(['name' => 'Italia standard'], ['country_code' => 'IT', 'zone' => 'italy', 'price_cents' => 690, 'is_active' => true]);
        ShippingRate::updateOrCreate(['name' => 'Europa'], ['country_code' => null, 'zone' => 'europe', 'price_cents' => 1490, 'is_active' => true]);
        ShippingRate::updateOrCreate(['name' => 'Worldwide'], ['country_code' => null, 'zone' => 'worldwide', 'price_cents' => 2490, 'is_active' => true]);

        DiscountCode::updateOrCreate(['code' => 'WELCOME10'], ['type' => 'percent', 'value' => 10, 'is_active' => true]);

        Setting::updateOrCreate(['key' => 'delivery_estimate'], ['value' => ['label' => '7-10 giorni lavorativi']]);
        Setting::updateOrCreate(['key' => 'integrations'], ['value' => ['stripe' => 'pending', 'brevo' => 'pending']]);
    }
}
