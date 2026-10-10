<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'barcode',
        'name',
        'category_id',
        'cost_price',
        'selling_price',
        'price_wholesale',
        'stock',
        'min_stock',
        'unit',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function customerPrices(): HasMany
    {
        return $this->hasMany(CustomerPrice::class);
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock;
    }

    /** Harga grosir fallback ke harga ecer bila belum diisi. */
    public function wholesalePrice(): int
    {
        return (int) ($this->price_wholesale ?? $this->selling_price);
    }

    /** Harga untuk pelanggan tertentu (harga khusus > grosir > ecer). */
    public function priceFor(?Customer $customer): int
    {
        if (! $customer) {
            return (int) $this->selling_price;
        }

        $custom = $this->relationLoaded('customerPrices')
            ? $this->customerPrices->firstWhere('customer_id', $customer->id)
            : null;

        if ($custom) {
            return (int) $custom->price;
        }

        return $customer->is_wholesale ? $this->wholesalePrice() : (int) $this->selling_price;
    }
}
