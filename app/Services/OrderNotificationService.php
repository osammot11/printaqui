<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;

class OrderNotificationService
{
    public function __construct(private readonly BrevoTransactionalEmailService $brevo)
    {
    }

    /**
     * @throws RequestException
     */
    public function sendConfirmationForPaidOrder(Order $order): bool
    {
        $lockedOrder = DB::transaction(function () use ($order) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->payment_status !== 'paid' || $lockedOrder->order_confirmation_sent_at) {
                return null;
            }

            return $lockedOrder;
        });

        if (! $lockedOrder) {
            return false;
        }

        $sent = $this->brevo->sendOrderConfirmation($lockedOrder);

        if (! $sent) {
            return false;
        }

        DB::transaction(function () use ($lockedOrder) {
            Order::whereKey($lockedOrder->id)
                ->whereNull('order_confirmation_sent_at')
                ->update([
                    'order_confirmation_sent_at' => now(),
                    'order_confirmation_failed_at' => null,
                ]);
        });

        return true;
    }

    public function markConfirmationFailed(Order $order): void
    {
        $order->update(['order_confirmation_failed_at' => now()]);
    }

    /**
     * @throws RequestException
     */
    public function sendTrackingUpdate(Order $order): bool
    {
        $lockedOrder = DB::transaction(function () use ($order) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $lockedOrder->tracking_url || ! $lockedOrder->tracking_number || $lockedOrder->tracking_notification_sent_at) {
                return null;
            }

            return $lockedOrder;
        });

        if (! $lockedOrder) {
            return false;
        }

        $sent = $this->brevo->sendTrackingUpdate($lockedOrder);

        if (! $sent) {
            return false;
        }

        DB::transaction(function () use ($lockedOrder) {
            Order::whereKey($lockedOrder->id)
                ->whereNull('tracking_notification_sent_at')
                ->update([
                    'tracking_notification_sent_at' => now(),
                    'tracking_notification_failed_at' => null,
                ]);
        });

        return true;
    }

    public function markTrackingFailed(Order $order): void
    {
        $order->update(['tracking_notification_failed_at' => now()]);
    }
}
