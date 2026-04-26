<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public const STATUSES = [
        'unfulfilled' => 'Non evaso',
        'waiting_files' => 'In attesa file',
        'file_issue' => 'Problema file',
        'in_production' => 'In produzione',
        'fulfilled' => 'Evaso',
        'cancelled' => 'Annullato',
    ];

    public const PAYMENT_STATUSES = [
        'pending' => 'In attesa',
        'paid' => 'Pagato',
        'failed' => 'Fallito',
        'refunded' => 'Rimborsato',
    ];

    protected $fillable = [
        'customer_id',
        'number',
        'status',
        'payment_status',
        'paid_at',
        'stripe_payment_intent_id',
        'stripe_refund_id',
        'currency',
        'discount_code_id',
        'discount_code',
        'subtotal_cents',
        'discount_cents',
        'shipping_cents',
        'total_cents',
        'refunded_cents',
        'shipping_address',
        'billing_address',
        'internal_notes',
        'tags',
        'carrier',
        'tracking_number',
        'tracking_url',
        'fulfilled_at',
        'refunded_at',
        'stock_decremented_at',
        'discount_usage_recorded_at',
        'order_confirmation_sent_at',
        'order_confirmation_failed_at',
        'tracking_notification_sent_at',
        'tracking_notification_failed_at',
    ];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'tags' => 'array',
            'paid_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'refunded_at' => 'datetime',
            'stock_decremented_at' => 'datetime',
            'discount_usage_recorded_at' => 'datetime',
            'order_confirmation_sent_at' => 'datetime',
            'order_confirmation_failed_at' => 'datetime',
            'tracking_notification_sent_at' => 'datetime',
            'tracking_notification_failed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function paymentStatusLabel(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status] ?? $this->payment_status;
    }

    public function isFulfilled(): bool
    {
        return $this->status === 'fulfilled';
    }
}
