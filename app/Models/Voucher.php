<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'min_purchase',
        'quota',
        'starts_at',
        'expires_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function isValidNow(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return $this->quota === null || $this->used_count < $this->quota;
    }

    public function statusLabel(): string
    {
        if (! $this->is_active) {
            return 'Nonaktif';
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'Kedaluwarsa';
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'Belum mulai';
        }

        if ($this->quota !== null && $this->used_count >= $this->quota) {
            return 'Kuota habis';
        }

        return 'Aktif';
    }

    /** Potongan harga untuk subtotal tertentu (0 = tidak valid). */
    public function discountFor(int $subtotal): int
    {
        if (! $this->isValidNow() || $subtotal < $this->min_purchase) {
            return 0;
        }

        return $this->type === 'percent'
            ? (int) round($subtotal * $this->value / 100)
            : min($this->value, $subtotal);
    }

    public function label(): string
    {
        return $this->type === 'percent'
            ? $this->value.'%'
            : 'Rp '.number_format($this->value, 0, ',', '.');
    }
}