<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'note',
        'user_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a stock movement and update the product stock atomically.
     */
    public static function record(Product $product, string $type, int $quantity, ?string $note = null, ?int $userId = null): self
    {
        return DB::transaction(function () use ($product, $type, $quantity, $note, $userId) {
            $product = Product::whereKey($product->getKey())->lockForUpdate()->firstOrFail();

            $stockBefore = $product->stock;
            $stockAfter = match ($type) {
                'in' => $stockBefore + $quantity,
                'out' => $stockBefore - $quantity,
                'adjustment' => $quantity,
            };

            abort_if($stockAfter < 0, 422, 'Stok tidak boleh negatif.');

            $product->update(['stock' => $stockAfter]);

            return static::create([
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'note' => $note,
                'user_id' => $userId,
            ]);
        });
    }
}
