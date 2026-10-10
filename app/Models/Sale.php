<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    protected $fillable = [
        'invoice_number',
        'subtotal',
        'discount',
        'tax',
        'total',
        'paid_amount',
        'change_amount',
        'is_credit',
        'settled_at',
        'payment_method',
        'user_id',
        'customer_id',
        'voucher_id',
        'voucher_discount',
    ];

    protected $casts = [
        'is_credit' => 'boolean',
        'settled_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(CustomerDeposit::class);
    }

    public function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function remainingAmount(): int
    {
        return max(0, $this->total - $this->paid_amount);
    }

    public function returnedTotal(): int
    {
        return (int) $this->returns()->sum('total');
    }

    /** Sisa yang bisa diretur per item (sudah dikurangi retur sebelumnya). */
    public function returnableItems()
    {
        return $this->items->map(function (SaleItem $item) {
            $returned = (int) $item->returnItems()->sum('quantity');

            return (object) [
                'item' => $item,
                'returned' => $returned,
                'available' => max(0, $item->quantity - $returned),
            ];
        })->filter(fn ($row) => $row->available > 0)->values();
    }

    /**
     * Nomor invoice harian dari tabel sequence (aman dari race condition).
     * Dipanggil di dalam transaksi checkout.
     */
    public static function nextInvoiceNumber(): string
    {
        $day = today()->toDateString();
        $prefix = 'INV-'.now()->format('Ymd').'-';

        $sequence = DB::table('sale_sequences')->where('day', $day)->lockForUpdate()->first();

        if (! $sequence) {
            try {
                DB::table('sale_sequences')->insert(['day' => $day, 'last_number' => 1]);
            } catch (QueryException $e) {
                // Hari lain sudah membuat baris bersamaan; ambil yang ada.
                $sequence = DB::table('sale_sequences')->where('day', $day)->lockForUpdate()->first();
                DB::table('sale_sequences')->where('day', $day)->update([
                    'last_number' => DB::raw('last_number + 1'),
                ]);

                return $prefix.str_pad((string) ((int) $sequence->last_number + 1), 4, '0', STR_PAD_LEFT);
            }

            return $prefix.'0001';
        }

        $next = (int) $sequence->last_number + 1;
        DB::table('sale_sequences')->where('day', $day)->update(['last_number' => $next]);

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
