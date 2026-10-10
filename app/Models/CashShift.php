<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashShift extends Model
{
    protected $fillable = [
        'user_id',
        'opening_balance',
        'expected_balance',
        'closing_balance',
        'opened_at',
        'closed_at',
        'note',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('closed_at');
    }

    /**
     * Kas harapan: modal awal + semua pembayaran tunai selama shift
     * (transaksi cash + setoran utang).
     */
    public function expectedCash(): int
    {
        $range = [$this->opened_at, $this->closed_at ?? now()];

        // Hanya transaksi milik kasir pemilik shift (BUG-19).
        $cashIn = (int) Sale::where('user_id', $this->user_id)
            ->where('payment_method', 'cash')
            ->where('is_credit', false)
            ->whereBetween('created_at', $range)
            ->sum('total');

        // Uang muka utang tunai: paid_amount dikurangi cicilan yang sudah tercatat
        // (agar tidak dihitung dua kali).
        $downPayments = (int) (Sale::where('user_id', $this->user_id)
            ->where('is_credit', true)
            ->where('payment_method', 'cash')
            ->whereBetween('created_at', $range)
            ->selectRaw('SUM(paid_amount - COALESCE((SELECT SUM(amount) FROM sale_payments sp WHERE sp.sale_id = sales.id), 0)) as dp')
            ->value('dp') ?? 0);

        // Cicilan utang: hanya yang benar-benar diterima tunai (BUG-18).
        $creditPayments = (int) SalePayment::query()
            ->where('user_id', $this->user_id)
            ->where('payment_method', 'cash')
            ->whereBetween('created_at', $range)
            ->sum('amount');

        // Retur tunai pada penjualan non-kredit mengeluarkan uang dari laci (BUG-21).
        // Uang retur keluar dari laci kasir yang melakukan transaksi awal,
        // bukan dari laci kasir yang memproses returnya.
        $cashRefunds = (int) SaleReturn::query()
            ->where('refund_method', 'cash')
            ->whereBetween('created_at', $range)
            ->whereHas('sale', fn ($q) => $q->where('user_id', $this->user_id)->where('is_credit', false))
            ->sum('total');

        return (int) $this->opening_balance + $cashIn + $downPayments + $creditPayments - $cashRefunds;
    }
}
