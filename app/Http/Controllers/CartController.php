<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PrintZone;
use App\Services\CartValidationService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function show(CartValidationService $cartValidation)
    {
        $cart = $this->validatedCart($cartValidation);

        return view('storefront.cart', [
            'cart' => $cart['cart'],
            'cartWarnings' => $cart['errors'],
        ]);
    }

    public function add(Request $request, Product $product)
    {
        $product->load(['activePrintZones']);

        $validated = $request->validate([
            'variant_quantities' => ['required', 'array'],
            'variant_quantities.*' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'print_zones' => ['nullable', 'array'],
            'print_zones.*' => ['integer'],
            'print_files' => ['nullable', 'array'],
            'print_files.*' => ['file', 'mimes:png,jpg,jpeg,pdf,svg', 'max:20480'],
        ]);

        $variantQuantities = collect($validated['variant_quantities'])
            ->filter(fn ($quantity) => (int) $quantity > 0)
            ->map(fn ($quantity) => (int) $quantity);

        if ($variantQuantities->isEmpty()) {
            return back()->withErrors(['variant_quantities' => 'Inserisci almeno una quantita per una variante.'])->withInput();
        }

        $variants = ProductVariant::where('product_id', $product->id)
            ->where('is_active', true)
            ->whereIn('id', $variantQuantities->keys())
            ->get()
            ->keyBy('id');

        if ($variants->count() !== $variantQuantities->count()) {
            return back()->withErrors(['variant_quantities' => 'Una o piu varianti non sono disponibili.'])->withInput();
        }

        foreach ($variantQuantities as $variantId => $requestedQuantity) {
            $variant = $variants->get((int) $variantId);

            if ($requestedQuantity > $variant->stock) {
                return back()->withErrors([
                    'variant_quantities' => "Stock insufficiente per {$variant->size} {$variant->color}. Disponibili: {$variant->stock}.",
                ])->withInput();
            }
        }

        $zoneIds = collect($validated['print_zones'] ?? [])->map(fn ($id) => (int) $id)->unique();
        $zones = PrintZone::where('product_id', $product->id)
            ->where('is_active', true)
            ->whereIn('id', $zoneIds)
            ->get()
            ->keyBy('id');

        foreach ($zoneIds as $zoneId) {
            if (! $request->hasFile("print_files.{$zoneId}")) {
                return back()->withErrors(["print_files.{$zoneId}" => 'Carica un file per ogni zona selezionata.'])->withInput();
            }
        }

        $files = [];
        foreach ($zones as $zone) {
            /** @var UploadedFile $file */
            $file = $request->file("print_files.{$zone->id}");
            $filename = Str::slug($zone->name).'_'.Str::uuid().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('cart-print-files', $filename);

            $files[$zone->id] = [
                'zone_id' => $zone->id,
                'zone_name' => $zone->name,
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ];
        }

        $quantity = $variantQuantities->sum();
        $printUnitPrice = $zones->sum('additional_price_cents');
        $unitPrice = $product->currentPriceCents() + $printUnitPrice;

        $cart = session('cart', []);
        $cart[] = [
            'id' => (string) Str::uuid(),
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_sku' => $product->sku,
            'base_unit_price_cents' => $product->currentPriceCents(),
            'print_unit_price_cents' => $printUnitPrice,
            'quantity' => $quantity,
            'line_total_cents' => $unitPrice * $quantity,
            'variant_quantities' => $variantQuantities->mapWithKeys(function ($quantity, $variantId) use ($variants) {
                $variant = $variants->get((int) $variantId);

                return [$variantId => [
                    'variant_id' => (int) $variantId,
                    'sku' => $variant?->sku,
                    'size' => $variant?->size,
                    'color' => $variant?->color,
                    'quantity' => $quantity,
                ]];
            })->values()->all(),
            'print_zones' => $zones->map(fn ($zone) => [
                'id' => $zone->id,
                'name' => $zone->name,
                'slug' => $zone->slug,
                'additional_price_cents' => $zone->additional_price_cents,
            ])->values()->all(),
            'print_files' => array_values($files),
        ];

        session(['cart' => $cart]);

        return redirect()->route('cart.show')->with('status', 'Prodotto aggiunto al carrello.');
    }

    public function remove(string $itemId)
    {
        $cart = collect(session('cart', []));
        $item = $cart->firstWhere('id', $itemId);

        foreach (Arr::get($item, 'print_files', []) as $file) {
            Storage::delete($file['stored_path']);
        }

        session(['cart' => $cart->reject(fn ($item) => $item['id'] === $itemId)->values()->all()]);

        return back()->with('status', 'Articolo rimosso.');
    }

    private function cart(): array
    {
        $items = collect(session('cart', []));

        return [
            'items' => $items,
            'subtotal_cents' => $items->sum('line_total_cents'),
            'count' => $items->sum('quantity'),
        ];
    }

    private function validatedCart(CartValidationService $cartValidation): array
    {
        $result = $cartValidation->validate(collect(session('cart', [])));

        if ($result['changed']) {
            session(['cart' => $result['items']->all()]);
        }

        return [
            'cart' => [
                'items' => $result['items'],
                'subtotal_cents' => $result['items']->sum('line_total_cents'),
                'count' => $result['items']->sum('quantity'),
            ],
            'errors' => $result['errors'],
        ];
    }
}
