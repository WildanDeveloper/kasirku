<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\UpdateChecker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

class UpdateController extends Controller
{
    public function __construct(private UpdateChecker $checker) {}

    /** Halaman "Apa Yang Baru" + status pembaruan. */
    public function whatsNew()
    {
        return view('updates.index', [
            'currentVersion' => $this->checker->currentVersion(),
            'releasedAt' => config('kasirku.released_at'),
            'latestVersion' => $this->checker->latestVersion(),
            'updateAvailable' => $this->checker->isUpdateAvailable(),
            'changelog' => config('kasirku.changelog', []),
            'repository' => config('kasirku.repository'),
        ]);
    }

    /** Endpoint untuk tombol "Perbarui" di header (polling ringan). */
    public function status()
    {
        return response()->json([
            'current_version' => $this->checker->currentVersion(),
            'latest_version' => $this->checker->latestVersion(),
            'update_available' => $this->checker->isUpdateAvailable(),
            'whats_new_url' => route('updates.index'),
            'apply_url' => route('updates.apply'),
        ])->header('Cache-Control', 'no-store');
    }

    /** Daftar path yang bukan milik user web (penyebab umum update gagal). */
    private function permissionIssues(): array
    {
        $issues = [];

        foreach (['.git', 'node_modules', 'vendor', 'storage'] as $folder) {
            $path = base_path($folder);

            if (! is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->getOwner() !== 'www-data') {
                    $issues[] = $folder.'/'.trim(str_replace(base_path(), '', $file->getPathname()), '/');

                    if (count($issues) >= 6) {
                        return $issues;
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Jalankan pembaruan: git pull + composer + build + migrate.
     * Hanya admin, dan hasil tiap langkah ditampilkan apa adanya.
     */
    public function apply(Request $request)
    {
        abort_unless($request->user()->is_admin, 403);

        if (! $this->checker->isUpdateAvailable()) {
            return back()->with('warning', 'Tidak ada versi baru yang tersedia.');
        }

        $base = base_path();
        // Perintah dijalankan lewat bash -lc agar PATH miliknya sama dengan shell server
        // (npm/composer sering tidak ada di PATH default php-fpm).
        $steps = [
            'Mengambil kode terbaru' => 'git pull --ff-only',
            'Memasang dependensi PHP' => 'COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction',
            'Memasang aset frontend' => 'npm ci',
            'Membangun aset aplikasi' => 'npm run build',
            'Memperbarui database' => 'php artisan migrate --force',
            'Membersihkan cache' => 'php artisan optimize:clear',
        ];

        $log = [];

        $permissionIssues = $this->permissionIssues();

        if ($permissionIssues !== []) {
            return view('updates.result', [
                'log' => [[
                    'step' => 'Pemeriksaan izin folder',
                    'ok' => false,
                    'output' => 'Ada file milik user lain yang tidak bisa diubah user web (www-data): '
                        .implode(', ', array_slice($permissionIssues, 0, 5))
                        .'\nJalankan: chown -R www-data:www-data '.base_path(),
                ]],
                'currentVersion' => $this->checker->currentVersion(),
                'latestVersion' => $this->checker->latestVersion(),
            ]);
        }

        foreach ($steps as $label => $command) {
            $result = Process::timeout(600)->path($base)->run($command);

            $log[] = [
                'step' => $label,
                'ok' => $result->successful(),
                'output' => trim($result->output()),
            ];

            if (! $result->successful()) {
                break;
            }
        }

        $this->checker->forget();

        ActivityLogger::record(
            'app.updated',
            'Menjalankan pembaruan aplikasi ke versi '.$this->checker->latestVersion(),
            null,
            ['berhasil' => collect($log)->every(fn ($row) => $row['ok'])]
        );

        return view('updates.result', [
            'log' => $log,
            'currentVersion' => $this->checker->currentVersion(),
            'latestVersion' => $this->checker->latestVersion(),
        ]);
    }
}