<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    protected $casts = [
        'is_credit' => 'boolean',
        'settled_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
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

    public function remainingAmount(): int
    {
        return max(0, $this->total - $this->paid_amount);
    }

    public static function nextInvoiceNumber(): string
    {
        $prefix = 'INV-'.now()->format('Ymd').'-';
        $count = static::whereDate('created_at', today())->count() + 1;

        return $prefix.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
