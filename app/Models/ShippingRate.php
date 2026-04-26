<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    protected $fillable = ['name', 'country_code', 'country_codes', 'zone', 'price_cents', 'is_free_shipping', 'is_active'];

    protected function casts(): array
    {
        return [
            'country_codes' => 'array',
            'is_free_shipping' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function countryCodes(): array
    {
        $codes = $this->country_codes ?: ($this->country_code ? [$this->country_code] : []);

        return collect($codes)
            ->filter()
            ->map(fn ($code) => strtoupper(trim($code)))
            ->unique()
            ->values()
            ->all();
    }

    public function isWorldwide(): bool
    {
        return empty($this->countryCodes());
    }

    public function isAvailableForCountry(?string $country): bool
    {
        if ($this->isWorldwide()) {
            return true;
        }

        if (blank($country)) {
            return false;
        }

        return in_array(strtoupper(trim($country)), $this->countryCodes(), true);
    }
}
