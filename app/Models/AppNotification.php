<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $table = 'app_notifications';

    protected $fillable = ['type', 'title', 'message', 'url', 'level', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    /**
     * Buat notifikasi, tapi jangan spam: cek pesan serupa dalam 6 jam terakhir.
     */
    public static function notify(string $type, string $title, string $message, ?string $url = null, string $level = 'info'): void
    {
        $recent = static::where('type', $type)
            ->where('message', $message)
            ->where('created_at', '>=', now()->subHours(6))
            ->exists();

        if ($recent) {
            return;
        }

        static::create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'url' => $url,
            'level' => $level,
        ]);

        self::sendWhatsApp($title.': '.$message);
    }

    /**
     * Kirim ke WhatsApp via gateway eksternal bila dikonfigurasi (Fonnte / WAHA).
     * Diabaikan diam-diam bila gateway tidak diatur.
     */
    private static function sendWhatsApp(string $text): void
    {
        $token = Setting::get('wa_token');
        $url = Setting::get('wa_url');

        if ($token === '' || $url === '') {
            return;
        }

        try {
            \Illuminate\Support\Facades\Http::timeout(5)->post($url, [
                'token' => $token,
                'message' => $text,
            ]);
        } catch (\Throwable $e) {
            // Notifikasi WhatsApp tidak boleh mengganggu aplikasi.
        }
    }
}