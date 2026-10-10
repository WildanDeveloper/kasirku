<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Deteksi versi baru dari GitHub (rilis terbaru repo publik).
 * Hasil di-cache 6 jam supaya tidak membanjiri API tiap buka halaman.
 */
class UpdateChecker
{
    private const CACHE_KEY = 'kasirku:latest_version';

    private const CACHE_MINUTES = 15;

    public function currentVersion(): string
    {
        return (string) config('kasirku.version', '0.0.0');
    }

    /** Versi terbaru di GitHub, atau null bila tidak bisa dicek. */
    public function latestVersion(): ?string
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_MINUTES), function () {
            try {
                $response = Http::timeout(4)
                    ->withHeaders(['Accept' => 'application/vnd.github+json', 'User-Agent' => 'Kasirku'])
                    ->get('https://api.github.com/repos/WildanDeveloper/kasirku/releases/latest');

                if (! $response->successful()) {
                    return null;
                }

                $tag = $response->json('tag_name');

                return is_string($tag) && $tag !== '' ? ltrim($tag, 'v') : null;
            } catch (\Throwable $e) {
                return null;
            }
        });
    }

    public function isUpdateAvailable(): bool
    {
        $latest = $this->latestVersion();

        if (! $latest) {
            return false;
        }

        return version_compare($latest, $this->currentVersion(), '>');
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}