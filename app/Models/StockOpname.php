<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpname extends Model
{
    protected $fillable = ['name', 'note', 'status', 'difference_value', 'finished_at', 'user_id'];

    protected $casts = ['finished_at' => 'datetime'];

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    public function totalDifference(): int
    {
        return (int) $this->items()->sum('difference');
    }
}