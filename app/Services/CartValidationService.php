<?php

namespace App\Services;

use App\Models\PrintZone;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

class CartValidationService
{
    public function validate(Collection $items): array
    {
        $validatedItems = collect();
        $errors = collect();

        foreach ($items as $item) {
            $result = $this->validateItem($item);

            if ($result['valid']) {
                $validatedItems->push($result['item']);
            } else {
                $errors = $errors->merge($result['errors']);
                $this->deletePrintFiles($item);
            }
        }

        return [
            'items' => $validatedItems->values(),
            'errors' => $errors->values(),
            'changed' => $errors->isNotEmpty() || $validatedItems->values()->all() !== $items->values()->all(),
        ];
    }

    private function validateItem(array $item): array
    {
        $errors = collect();
        $product = Product::find($item['product_id'] ?? null);

        if (! $product || ! $product->is_active) {
            return [
                'valid' => false,
                'errors' => ['Un prodotto nel carrello non e piu disponibile.'],
            ];
        }

        $variantRows = collect($item['variant_quantities'] ?? []);
        $variantIds = $variantRows->pluck('variant_id')->map(fn ($id) => (int) $id)->filter()->values();
        $variants = ProductVariant::where('product_id', $product->id)
            ->where('is_active', true)
            ->whereIn('id', $variantIds)
            ->get()
            ->keyBy('id');

        if ($variants->count() !== $variantIds->unique()->count()) {
            $errors->push("Una variante di {$product->name} non e piu disponibile.");
        }

        $validatedVariants = $variantRows->map(function ($row) use ($variants, $product, $errors) {
            $variant = $variants->get((int) ($row['variant_id'] ?? 0));
            $quantity = (int) ($row['quantity'] ?? 0);

            if (! $variant) {
                return null;
            }

            if ($quantity > $variant->stock) {
                $errors->push("Stock insufficiente per {$product->name} {$variant->size} {$variant->color}. Disponibili: {$variant->stock}.");

                return null;
            }

            return [
                'variant_id' => $variant->id,
                'sku' => $variant->sku,
                'size' => $variant->size,
                'color' => $variant->color,
                'quantity' => $quantity,
            ];
        })->filter()->values();

        $zoneRows = collect($item['print_zones'] ?? []);
        $zoneIds = $zoneRows->pluck('id')->map(fn ($id) => (int) $id)->filter()->values();
        $zones = PrintZone::where('product_id', $product->id)
            ->where('is_active', true)
            ->whereIn('id', $zoneIds)
            ->get()
            ->keyBy('id');

        if ($zones->count() !== $zoneIds->unique()->count()) {
            $errors->push("Una zona stampa di {$product->name} non e piu disponibile.");
        }

        $validatedZones = $zoneRows->map(function ($row) use ($zones) {
            $zone = $zones->get((int) ($row['id'] ?? 0));

            if (! $zone) {
                return null;
            }

            return [
                'id' => $zone->id,
                'name' => $zone->name,
                'slug' => $zone->slug,
                'additional_price_cents' => $zone->additional_price_cents,
            ];
        })->filter()->values();

        if ($errors->isNotEmpty()) {
            return [
                'valid' => false,
                'errors' => $errors->all(),
            ];
        }

        $quantity = $validatedVariants->sum('quantity');
        $baseUnitPrice = $product->currentPriceCents();
        $printUnitPrice = $validatedZones->sum('additional_price_cents');

        return [
            'valid' => true,
            'errors' => [],
            'item' => array_merge($item, [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_slug' => $product->slug,
                'product_sku' => $product->sku,
                'base_unit_price_cents' => $baseUnitPrice,
                'print_unit_price_cents' => $printUnitPrice,
                'quantity' => $quantity,
                'line_total_cents' => ($baseUnitPrice + $printUnitPrice) * $quantity,
                'variant_quantities' => $validatedVariants->all(),
                'print_zones' => $validatedZones->all(),
            ]),
        ];
    }

    private function deletePrintFiles(array $item): void
    {
        foreach ($item['print_files'] ?? [] as $file) {
            if (filled($file['stored_path'] ?? null)) {
                \Illuminate\Support\Facades\Storage::delete($file['stored_path']);
            }
        }
    }
}
