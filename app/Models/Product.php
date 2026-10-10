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

    public function priceTiers(): HasMany
    {
        return $this->hasMany(PriceTier::class);
    }

    /**
     * Harga grosir bertingkat: tier dengan min_qty terbesar yang <= jumlah pembelian.
     * Kalau tidak ada tier yang cocok, pakai harga grosir / ecer.
     */
    public function tierPrice(int $quantity): int
    {
        $tier = $this->relationLoaded('priceTiers')
            ? $this->priceTiers->where('min_qty', '<=', $quantity)->sortByDesc('min_qty')->first()
            : $this->priceTiers()->where('min_qty', '<=', $quantity)->orderByDesc('min_qty')->first();

        if ($tier) {
            return (int) $tier->price;
        }

        return $this->wholesalePrice();
    }

    /** Harga akhir untuk pelanggan + jumlah (khusus > tier > grosir > ecer). */
    public function finalPriceFor(?Customer $customer, int $quantity = 1): int
    {
        if ($customer) {
            $custom = $this->relationLoaded('customerPrices')
                ? $this->customerPrices->firstWhere('customer_id', $customer->id)
                : $this->customerPrices()->where('customer_id', $customer->id)->first();

            if ($custom) {
                return (int) $custom->price;
            }

            if ($customer->is_wholesale) {
                return $this->tierPrice($quantity);
            }
        }

        return $this->priceTiers()->where('min_qty', '<=', $quantity)->exists()
            ? $this->tierPrice($quantity)
            : (int) $this->selling_price;
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

    /** Harga untuk pelanggan tertentu (khusus > tier grosir > grosir > ecer). */
    public function priceFor(?Customer $customer, int $quantity = 1): int
    {
        return $this->finalPriceFor($customer, $quantity);
    }

}
