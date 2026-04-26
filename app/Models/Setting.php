<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public static function getValue(string $key, array $default = []): array
    {
        return static::query()->firstWhere('key', $key)?->value ?? $default;
    }

    public static function putValue(string $key, array $value): self
    {
        return static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function deliveryEstimateLabel(): string
    {
        return static::getValue('delivery_estimate', ['label' => '7-10 giorni lavorativi'])['label']
            ?? '7-10 giorni lavorativi';
    }
}
