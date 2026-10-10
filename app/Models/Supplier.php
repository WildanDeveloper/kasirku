<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = ['name', 'phone', 'address', 'note'];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /** Sisa utang ke supplier ini. */
    public function outstanding(): int
    {
        return (int) $this->purchases()
            ->where('status', 'received')
            ->selectRaw('COALESCE(SUM(total - paid_amount), 0) as sisa')
            ->value('sisa');
    }
}