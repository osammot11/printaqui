<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteRequest extends Model
{
    protected $fillable = [
        'number',
        'status',
        'first_name',
        'last_name',
        'email',
        'phone',
        'company',
        'product_type',
        'quantity',
        'print_positions',
        'deadline',
        'message',
        'internal_notes',
        'artwork_original_name',
        'artwork_path',
        'artwork_mime_type',
        'artwork_size_bytes',
        'admin_notification_sent_at',
        'admin_notification_failed_at',
        'customer_confirmation_sent_at',
        'customer_confirmation_failed_at',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'admin_notification_sent_at' => 'datetime',
            'admin_notification_failed_at' => 'datetime',
            'customer_confirmation_sent_at' => 'datetime',
            'customer_confirmation_failed_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public const STATUSES = [
        'new' => 'Nuova',
        'reviewing' => 'In valutazione',
        'responded' => 'Risposto',
        'won' => 'Vinta',
        'lost' => 'Persa',
    ];

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
