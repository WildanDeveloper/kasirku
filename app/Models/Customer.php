<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['name', 'phone', 'address', 'is_wholesale', 'points'];

    protected $casts = ['is_wholesale' => 'boolean', 'points' => 'integer'];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(CustomerPrice::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(CustomerDeposit::class);
    }

    public function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    /** Saldo kasbon (deposit) pelanggan. */
    public function depositBalance(): int
    {
        return (int) $this->deposits()
            ->selectRaw('COALESCE(SUM(CASE WHEN type = \'in\' THEN amount ELSE -amount END), 0) as saldo')
            ->value('saldo');
    }

    /** Nilai tukar 1 poin dalam rupiah (dari pengaturan). */
    public function pointValue(): int
    {
        return max(1, (int) Setting::get('loyalty_point_value'));
    }

    public function pointValueTotal(): int
    {
        return (int) $this->points * $this->pointValue();
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