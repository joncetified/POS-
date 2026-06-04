<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'sku',
        'barcode',
        'name',
        'price',
        'stock',
        'unit',
        'tag',
        'color',
        'image_path',
        'is_bundle',
        'is_active',
    ];

    protected $casts = [
        'price' => 'integer',
        'stock' => 'integer',
        'is_bundle' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function bundleItems(): HasMany
    {
        return $this->hasMany(ProductBundleItem::class, 'bundle_product_id');
    }

    public function usedInBundles(): HasMany
    {
        return $this->hasMany(ProductBundleItem::class, 'component_product_id');
    }

    public function availableForSaleStock(): int
    {
        if (! $this->is_bundle) {
            return $this->stock;
        }

        $items = $this->relationLoaded('bundleItems')
            ? $this->bundleItems
            : $this->bundleItems()->with('component')->get();

        if ($items->isEmpty()) {
            return 0;
        }

        $componentStock = $items
            ->map(fn (ProductBundleItem $item) => $item->component && $item->quantity > 0
                ? intdiv($item->component->stock, $item->quantity)
                : 0)
            ->min();

        return min($this->stock, (int) $componentStock);
    }
}
