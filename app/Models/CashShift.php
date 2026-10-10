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

        // Penjualan tunai biasa: kas masuk bersih = total (diterima dikurangi kembalian).
        $cashIn = Sale::where('payment_method', 'cash')
            ->where('is_credit', false)
            ->whereBetween('created_at', $range)
            ->sum('total');

        // Uang muka transaksi utang tunai: paid_amount dikurangi cicilan yang sudah masuk
        // lewat menu pembayaran (agar tidak dihitung dua kali).
        $creditDownPayments = (float) (Sale::where('is_credit', true)
            ->where('payment_method', 'cash')
            ->whereBetween('created_at', $range)
            ->selectRaw('SUM(paid_amount - COALESCE((SELECT SUM(amount) FROM sale_payments sp WHERE sp.sale_id = sales.id), 0)) as dp')
            ->value('dp') ?? 0);

        // Cicilan utang yang diterima tunai selama shift.
        $creditPayments = SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->where('sales.payment_method', 'cash')
            ->whereBetween('sale_payments.created_at', $range)
            ->sum('sale_payments.amount');

        return (int) $this->opening_balance + (int) $cashIn + (int) $creditDownPayments + (int) $creditPayments;
    }
}
