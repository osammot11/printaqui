<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'base_price_cents',
        'sale_price_cents',
        'internal_cost_cents',
        'media',
        'size_chart',
        'estimated_delivery',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'media' => 'array',
            'size_chart' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function printZones(): HasMany
    {
        return $this->hasMany(PrintZone::class)->orderBy('sort_order');
    }

    public function activePrintZones(): HasMany
    {
        return $this->printZones()->where('is_active', true);
    }

    public function currentPriceCents(): int
    {
        return $this->sale_price_cents ?? $this->base_price_cents;
    }

    public function mediaItems(): array
    {
        return collect($this->media ?? [])
            ->map(fn ($item) => $this->normalizeMediaItem($item))
            ->filter()
            ->values()
            ->all();
    }

    public function primaryMediaUrl(): ?string
    {
        $items = $this->mediaItems();

        return Arr::first($items, fn ($item) => $item['is_primary'] ?? false)['url']
            ?? Arr::first($items)['url']
            ?? null;
    }

    public function galleryMediaUrls(): array
    {
        return collect($this->mediaItems())
            ->pluck('url')
            ->filter()
            ->values()
            ->all();
    }

    public function sizeChartUrl(): ?string
    {
        if (blank($this->size_chart)) {
            return null;
        }

        if (is_array($this->size_chart)) {
            return filled($this->size_chart['path'] ?? null)
                ? $this->publicStorageUrl($this->size_chart['path'])
                : ($this->size_chart['url'] ?? null);
        }

        return $this->size_chart;
    }

    private function normalizeMediaItem(string|array|null $item): ?array
    {
        if (blank($item)) {
            return null;
        }

        if (is_string($item)) {
            return [
                'key' => md5($item),
                'url' => $item,
                'path' => null,
                'original_name' => basename(parse_url($item, PHP_URL_PATH) ?: $item),
                'is_primary' => false,
            ];
        }

        $url = filled($item['path'] ?? null)
            ? $this->publicStorageUrl($item['path'])
            : ($item['url'] ?? null);

        if (blank($url)) {
            return null;
        }

        return [
            'key' => $item['key'] ?? md5($item['path'] ?? $url),
            'url' => $url,
            'path' => $item['path'] ?? null,
            'original_name' => $item['original_name'] ?? basename(parse_url($url, PHP_URL_PATH) ?: $url),
            'is_primary' => (bool) ($item['is_primary'] ?? false),
        ];
    }

    private function publicStorageUrl(string $path): string
    {
        return '/storage/'.ltrim($path, '/');
    }
}
