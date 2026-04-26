<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use RuntimeException;
use Stripe\Event;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\StripeClient;
use Stripe\StripeObject;
use Stripe\Webhook;
use Throwable;

class StripeCheckoutService
{
    public function __construct(
        private readonly OrderStockService $orderStock,
        private readonly DiscountService $discounts,
        private readonly OrderNotificationService $notifications,
    )
    {
    }

    public function isConfigured(): bool
    {
        return filled(config('services.stripe.key')) && filled(config('services.stripe.secret'));
    }

    public function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Stripe non e configurato. Controlla STRIPE_KEY e STRIPE_SECRET nel file .env.');
        }
    }

    public function publicKey(): string
    {
        return (string) config('services.stripe.key');
    }

    public function createPaymentIntent(Order $order, ?Customer $customer = null): PaymentIntent
    {
        $this->assertConfigured();

        return $this->client()->paymentIntents->create([
            'amount' => $order->total_cents,
            'currency' => strtolower((string) config('services.stripe.currency', 'EUR')),
            'payment_method_types' => config('services.stripe.payment_methods', ['card']),
            'receipt_email' => $customer?->email,
            'description' => 'Ordine '.$order->number,
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => $order->number,
            ],
        ]);
    }

    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        $this->assertConfigured();

        return $this->client()->paymentIntents->retrieve($paymentIntentId);
    }

    public function refundOrder(Order $order, ?string $reason = null): Refund
    {
        $this->assertConfigured();

        if ($order->payment_status !== 'paid') {
            throw new RuntimeException('Puoi rimborsare solo ordini pagati.');
        }

        if (! $order->stripe_payment_intent_id) {
            throw new RuntimeException('Questo ordine non ha un PaymentIntent Stripe collegato.');
        }

        if ($order->refunded_at || $order->payment_status === 'refunded') {
            throw new RuntimeException('Questo ordine risulta gia rimborsato.');
        }

        $refund = $this->client()->refunds->create([
            'payment_intent' => $order->stripe_payment_intent_id,
            'amount' => $order->total_cents,
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => $order->number,
                'admin_reason' => (string) $reason,
            ],
        ]);

        $this->markOrderRefunded($order, $refund, $reason);

        return $refund;
    }

    public function eventFromWebhook(string $payload, ?string $signature): Event
    {
        $secret = config('services.stripe.webhook_secret');

        if (filled($secret)) {
            return Webhook::constructEvent($payload, $signature ?? '', $secret);
        }

        return Event::constructFrom(json_decode($payload, true));
    }

    public function syncOrderFromPaymentIntent(PaymentIntent|StripeObject $paymentIntent): ?Order
    {
        $order = $this->findOrderForPaymentIntent($paymentIntent);

        if (! $order) {
            return null;
        }

        $status = match ($paymentIntent->status) {
            'succeeded' => 'paid',
            'processing' => 'processing',
            'requires_payment_method', 'requires_action', 'canceled' => 'failed',
            default => 'pending',
        };

        $order->update([
            'payment_status' => $status,
            'paid_at' => $status === 'paid' ? ($order->paid_at ?: now()) : $order->paid_at,
            'stripe_payment_intent_id' => $paymentIntent->id,
        ]);

        if ($status === 'paid') {
            try {
                $this->orderStock->decrementForPaidOrder($order);
            } catch (RuntimeException $exception) {
                report($exception);
                $this->appendInternalNote($order, 'ATTENZIONE stock non scalato automaticamente: '.$exception->getMessage());
            }

            $this->discounts->recordUsageForPaidOrder($order);

            try {
                $this->notifications->sendConfirmationForPaidOrder($order);
            } catch (Throwable $exception) {
                report($exception);
                $this->notifications->markConfirmationFailed($order);
                $this->appendInternalNote($order, 'ATTENZIONE email conferma ordine non inviata: '.$exception->getMessage());
            }
        }

        return $order;
    }

    private function client(): StripeClient
    {
        return new StripeClient((string) config('services.stripe.secret'));
    }

    private function findOrderForPaymentIntent(PaymentIntent|StripeObject $paymentIntent): ?Order
    {
        $orderId = $paymentIntent->metadata['order_id'] ?? null;

        if ($orderId) {
            return Order::find($orderId);
        }

        return Order::where('stripe_payment_intent_id', $paymentIntent->id)->first();
    }

    private function appendInternalNote(Order $order, string $note): void
    {
        $prefix = '['.now()->format('Y-m-d H:i').'] ';
        $currentNotes = trim((string) $order->internal_notes);

        $order->update([
            'internal_notes' => trim($currentNotes."\n".$prefix.$note),
        ]);
    }

    private function markOrderRefunded(Order $order, Refund $refund, ?string $reason): void
    {
        $prefix = '['.now()->format('Y-m-d H:i').'] ';
        $currentNotes = trim((string) $order->internal_notes);
        $note = 'Rimborso Stripe completato: '.$refund->id.'.';

        if (filled($reason)) {
            $note .= ' Motivo: '.$reason;
        }

        $order->update([
            'payment_status' => 'refunded',
            'stripe_refund_id' => $refund->id,
            'refunded_cents' => $order->total_cents,
            'refunded_at' => now(),
            'internal_notes' => trim($currentNotes."\n".$prefix.$note),
        ]);
    }
}
