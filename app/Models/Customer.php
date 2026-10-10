<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['name', 'phone', 'address', 'is_wholesale'];

    protected $casts = ['is_wholesale' => 'boolean'];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(CustomerPrice::class);
    }

    /** Sisa utang pelanggan dari semua transaksi kredit. */
    public function outstanding(): int
    {
        return (int) $this->sales()
            ->where('is_credit', true)
            ->whereNull('settled_at')
            ->selectRaw('COALESCE(SUM(total - paid_amount), 0) as sisa')
            ->value('sisa');
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $first = mb_substr($parts[0] ?? '?', 0, 1);
        $last = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';

        return mb_strtoupper($first.$last);
    }
}