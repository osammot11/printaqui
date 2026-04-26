<?php

namespace App\Services;

use App\Models\DiscountCode;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DiscountService
{
    public function resolve(?string $code): ?DiscountCode
    {
        if (blank($code)) {
            return null;
        }

        $normalizedCode = Str::upper(trim($code));

        $discount = DiscountCode::where('code', $normalizedCode)->first();

        if (! $discount || ! $this->isUsable($discount)) {
            throw ValidationException::withMessages([
                'coupon' => 'Coupon non valido o non piu utilizzabile.',
            ]);
        }

        return $discount;
    }

    public function amountForSubtotalCents(DiscountCode $discount, int $subtotalCents): int
    {
        if ($subtotalCents <= 0) {
            return 0;
        }

        $amount = match ($discount->type) {
            'percent' => (int) round($subtotalCents * min($discount->value, 100) / 100),
            'fixed', 'amount' => $discount->value,
            default => 0,
        };

        return min($subtotalCents, max(0, $amount));
    }

    public function recordUsageForPaidOrder(Order $order): bool
    {
        return DB::transaction(function () use ($order) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->payment_status !== 'paid' || ! $lockedOrder->discount_code_id || $lockedOrder->discount_usage_recorded_at) {
                return false;
            }

            $discount = DiscountCode::whereKey($lockedOrder->discount_code_id)->lockForUpdate()->first();

            if (! $discount) {
                return false;
            }

            $discount->increment('used_count');
            $lockedOrder->update(['discount_usage_recorded_at' => now()]);

            return true;
        });
    }

    private function isUsable(DiscountCode $discount): bool
    {
        if (! $discount->is_active) {
            return false;
        }

        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            return false;
        }

        if ($discount->ends_at && $discount->ends_at->isPast()) {
            return false;
        }

        if ($discount->usage_limit !== null && $discount->used_count >= $discount->usage_limit) {
            return false;
        }

        return in_array($discount->type, ['percent', 'fixed', 'amount'], true);
    }
}
