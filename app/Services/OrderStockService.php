<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class OrderStockService
{
    public function assertCartIsAvailable(Collection $cartItems): void
    {
        $quantities = $this->variantQuantitiesFromCartItems($cartItems);

        if ($quantities->isEmpty()) {
            return;
        }

        $variants = ProductVariant::whereIn('id', $quantities->keys())
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        foreach ($quantities as $variantId => $quantity) {
            $variant = $variants->get($variantId);

            if (! $variant) {
                throw ValidationException::withMessages([
                    'cart' => 'Una o piu varianti nel carrello non sono piu disponibili.',
                ]);
            }

            if ($quantity > $variant->stock) {
                throw ValidationException::withMessages([
                    'cart' => "Stock insufficiente per {$variant->size} {$variant->color}. Disponibili: {$variant->stock}.",
                ]);
            }
        }
    }

    public function decrementForPaidOrder(Order $order): bool
    {
        return DB::transaction(function () use ($order) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->payment_status !== 'paid' || $lockedOrder->stock_decremented_at) {
                return false;
            }

            $lockedOrder->load('items');
            $quantities = $this->variantQuantitiesFromOrder($lockedOrder);

            if ($quantities->isEmpty()) {
                $lockedOrder->update(['stock_decremented_at' => now()]);

                return true;
            }

            $variants = ProductVariant::whereIn('id', $quantities->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($quantities as $variantId => $quantity) {
                $variant = $variants->get($variantId);

                if (! $variant) {
                    throw new RuntimeException("Variante ordine non trovata: {$variantId}.");
                }

                if ($quantity > $variant->stock) {
                    throw new RuntimeException("Stock insufficiente per {$variant->size} {$variant->color}. Richiesti: {$quantity}, disponibili: {$variant->stock}.");
                }
            }

            foreach ($quantities as $variantId => $quantity) {
                $variant = $variants->get($variantId);
                $variant->update(['stock' => $variant->stock - $quantity]);
            }

            $lockedOrder->update(['stock_decremented_at' => now()]);

            return true;
        });
    }

    private function variantQuantitiesFromCartItems(Collection $cartItems): Collection
    {
        return $cartItems
            ->flatMap(fn ($item) => $item['variant_quantities'] ?? [])
            ->filter(fn ($variant) => isset($variant['variant_id'], $variant['quantity']))
            ->groupBy(fn ($variant) => (int) $variant['variant_id'])
            ->map(fn ($entries) => $entries->sum(fn ($variant) => (int) $variant['quantity']))
            ->filter(fn ($quantity) => $quantity > 0);
    }

    private function variantQuantitiesFromOrder(Order $order): Collection
    {
        return $order->items
            ->flatMap(fn ($item) => $item->variant_quantities ?? [])
            ->filter(fn ($variant) => isset($variant['variant_id'], $variant['quantity']))
            ->groupBy(fn ($variant) => (int) $variant['variant_id'])
            ->map(fn ($entries) => $entries->sum(fn ($variant) => (int) $variant['quantity']))
            ->filter(fn ($quantity) => $quantity > 0);
    }
}
