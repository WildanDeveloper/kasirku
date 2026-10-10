<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = [
        'invoice_number',
        'supplier_id',
        'purchase_date',
        'subtotal',
        'discount',
        'total',
        'paid_amount',
        'status',
        'note',
        'user_id',
    ];

    protected $casts = [
        'purchase_date' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function outstanding(): int
    {
        return $this->status === 'cancelled'
            ? 0
            : max(0, (int) $this->total - (int) $this->paid_amount);
    }

    public function isPaid(): bool
    {
        return $this->outstanding() === 0;
    }

    public static function nextInvoiceNumber(): string
    {
        $day = today()->toDateString();
        $prefix = 'PO-'.now()->format('Ymd').'-';

        $last = static::whereDate('created_at', today())
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}