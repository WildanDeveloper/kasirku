<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = ['description', 'category', 'amount', 'spent_at', 'user_id'];

    protected $casts = ['spent_at' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
