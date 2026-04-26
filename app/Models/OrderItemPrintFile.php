<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class OrderItemPrintFile extends Model
{
    protected $fillable = [
        'order_item_id',
        'print_zone_id',
        'zone_name',
        'original_name',
        'stored_path',
        'mime_type',
        'size_bytes',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function printZone(): BelongsTo
    {
        return $this->belongsTo(PrintZone::class);
    }
}
