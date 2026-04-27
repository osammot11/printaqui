<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.products.index', [
            'products' => Product::with(['category', 'variants', 'printZones'])->latest()->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.products.form', [
            'product' => new Product(),
            'categories' => Category::orderBy('name')->get(),
            'defaultDeliveryEstimate' => Setting::deliveryEstimateLabel(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $product = DB::transaction(function () use ($request) {
            $product = Product::create($this->validatedProduct($request));
            $variants = $this->preparedVariants($request, $product);
            $zones = $this->preparedPrintZones($request);

            $this->syncMedia($request, $product);
            $this->syncVariantsAndZones($product, $variants, $zones);

            return $product;
        });

        return redirect()->route('admin.products.edit', $product)->with('status', 'Prodotto creato.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->route('admin.products.edit', $id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $product->load(['variants', 'printZones']);

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(),
            'defaultDeliveryEstimate' => Setting::deliveryEstimateLabel(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        DB::transaction(function () use ($request, $product) {
            $product->fill($this->validatedProduct($request));
            $variants = $this->preparedVariants($request, $product);
            $zones = $this->preparedPrintZones($request);

            $product->save();
            $this->syncMedia($request, $product);
            $product->variants()->delete();
            $product->printZones()->delete();
            $this->syncVariantsAndZones($product, $variants, $zones);
        });

        return redirect()->route('admin.products.edit', $product)->with('status', 'Prodotto aggiornato.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->update(['is_active' => false]);

        return redirect()->route('admin.products.index')->with('status', 'Prodotto disattivato.');
    }

    private function validatedProduct(Request $request): array
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:180'],
            'sku' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'internal_cost' => ['nullable', 'numeric', 'min:0'],
            'estimated_delivery' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'media_images' => ['nullable', 'array'],
            'media_images.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,avif,svg', 'max:10240'],
            'primary_media_key' => ['nullable', 'string'],
            'remove_media' => ['nullable', 'array'],
            'remove_media.*' => ['string'],
            'size_chart_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,avif,pdf', 'max:10240'],
            'remove_size_chart' => ['nullable', 'boolean'],
            'variants' => ['nullable', 'array'],
            'variants.*.size' => ['nullable', 'string', 'max:40'],
            'variants.*.color' => ['nullable', 'string', 'max:80'],
            'variants.*.hex_color' => ['nullable', 'string', 'max:7'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'variants.*.sku' => ['nullable', 'string', 'max:80'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            'print_zones' => ['nullable', 'array'],
            'print_zones.*.name' => ['nullable', 'string', 'max:120'],
            'print_zones.*.price' => ['nullable', 'numeric', 'min:0'],
            'print_zones.*.is_active' => ['nullable', 'boolean'],
        ]);

        return [
            'category_id' => $validated['category_id'] ?? null,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.Str::lower($validated['sku']),
            'sku' => $validated['sku'],
            'description' => $validated['description'] ?? null,
            'base_price_cents' => (int) round($validated['base_price'] * 100),
            'sale_price_cents' => isset($validated['sale_price']) ? (int) round($validated['sale_price'] * 100) : null,
            'internal_cost_cents' => isset($validated['internal_cost']) ? (int) round($validated['internal_cost'] * 100) : null,
            'estimated_delivery' => $validated['estimated_delivery'],
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function syncMedia(Request $request, Product $product): void
    {
        $removeKeys = collect($request->input('remove_media', []));
        $media = collect($product->mediaItems())
            ->reject(function ($item) use ($removeKeys) {
                if (! $removeKeys->contains($item['key'])) {
                    return false;
                }

                if ($item['path']) {
                    Storage::disk('public')->delete($item['path']);
                }

                return true;
            })
            ->values();

        foreach ($request->file('media_images', []) as $file) {
            $path = $file->store("products/{$product->id}/media", 'public');

            $media->push([
                'key' => md5($path),
                'url' => $this->publicStorageUrl($path),
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'is_primary' => false,
            ]);
        }

        $primaryKey = $request->input('primary_media_key');
        $hasPrimary = false;

        $media = $media->map(function ($item, $index) use ($primaryKey, &$hasPrimary) {
            $item['is_primary'] = filled($primaryKey)
                ? $item['key'] === $primaryKey
                : $index === 0;

            $hasPrimary = $hasPrimary || $item['is_primary'];

            return $item;
        });

        if (! $hasPrimary && $media->isNotEmpty()) {
            $media = $media->map(function ($item, $index) {
                $item['is_primary'] = $index === 0;

                return $item;
            });
        }

        $updates = ['media' => $media->values()->all()];

        if ($request->boolean('remove_size_chart')) {
            if (is_array($product->size_chart) && filled($product->size_chart['path'] ?? null)) {
                Storage::disk('public')->delete($product->size_chart['path']);
            }

            $updates['size_chart'] = null;
        }

        if ($request->hasFile('size_chart_file')) {
            if (is_array($product->size_chart) && filled($product->size_chart['path'] ?? null)) {
                Storage::disk('public')->delete($product->size_chart['path']);
            }

            $file = $request->file('size_chart_file');
            $path = $file->store("products/{$product->id}/size-charts", 'public');

            $updates['size_chart'] = [
                'path' => $path,
                'url' => $this->publicStorageUrl($path),
                'original_name' => $file->getClientOriginalName(),
            ];
        }

        $product->update($updates);
    }

    private function publicStorageUrl(string $path): string
    {
        return '/storage/'.ltrim($path, '/');
    }

    private function preparedVariants(Request $request, Product $product): Collection
    {
        $variants = collect($request->input('variants', []))
            ->filter(fn ($variant) => filled($variant['size'] ?? null) && filled($variant['color'] ?? null))
            ->map(function ($variant) use ($product) {
                $sku = filled($variant['sku'] ?? null)
                    ? trim($variant['sku'])
                    : $product->sku.'-'.trim($variant['size']).'-'.Str::slug($variant['color']);

                return [
                    'sku' => $sku,
                    'size' => trim($variant['size']),
                    'color' => trim($variant['color']),
                    'hex_color' => filled($variant['hex_color'] ?? null) ? trim($variant['hex_color']) : null,
                    'stock' => (int) ($variant['stock'] ?? 0),
                    'is_active' => isset($variant['is_active']),
                ];
            })
            ->values();

        $duplicateSku = $variants
            ->groupBy(fn ($variant) => Str::lower($variant['sku']))
            ->first(fn ($items) => $items->count() > 1);

        if ($duplicateSku) {
            throw ValidationException::withMessages([
                'variants' => 'SKU variante duplicato: '.$duplicateSku->first()['sku'],
            ]);
        }

        $skus = $variants->pluck('sku')->filter()->values();

        if ($skus->isNotEmpty()) {
            $conflictingSku = ProductVariant::whereIn('sku', $skus)
                ->where('product_id', '!=', $product->id)
                ->value('sku');

            if ($conflictingSku) {
                throw ValidationException::withMessages([
                    'variants' => 'SKU variante gia usato da un altro prodotto: '.$conflictingSku,
                ]);
            }
        }

        return $variants;
    }

    private function preparedPrintZones(Request $request): Collection
    {
        $zones = collect($request->input('print_zones', []))
            ->filter(fn ($zone) => filled($zone['name'] ?? null))
            ->map(fn ($zone, $index) => [
                'name' => trim($zone['name']),
                'slug' => Str::slug($zone['name']),
                'additional_price_cents' => (int) round(((float) ($zone['price'] ?? 0)) * 100),
                'is_active' => isset($zone['is_active']),
                'sort_order' => (int) $index,
            ])
            ->values();

        $duplicateSlug = $zones
            ->groupBy('slug')
            ->first(fn ($items) => $items->count() > 1);

        if ($duplicateSlug) {
            throw ValidationException::withMessages([
                'print_zones' => 'Zona stampa duplicata: '.$duplicateSlug->first()['name'],
            ]);
        }

        return $zones;
    }

    private function syncVariantsAndZones(Product $product, Collection $variants, Collection $zones): void
    {
        foreach ($variants as $variant) {
            $product->variants()->create($variant);
        }

        foreach ($zones as $zone) {
            $product->printZones()->create($zone);
        }
    }
}
