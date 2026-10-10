<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Pencatatan aktivitas penting (audit trail) untuk toko dengan beberapa kasir.
 * Sengaja dibungkus try/catch: gagal menulis log tidak boleh membatalkan transaksi.
 */
class ActivityLogger
{
    public static function record(
        string $action,
        string $description,
        ?object $subject = null,
        array $properties = []
    ): void {
        try {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'description' => $description,
                'properties' => $properties ?: null,
                'ip_address' => Request::ip(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gagal menulis activity log: '.$e->getMessage());
        }
    }
}