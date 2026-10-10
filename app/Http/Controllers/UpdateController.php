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

    /**
     * Jalankan satu langkah pembaruan.
     *
     * Penting: perintah dijalankan lewat `bash -lc` dengan HOME/PATH yang eksplisit.
     * Kalau tidak, php-fpm mewarisi environment kosong sehingga npm/composer gagal
     * dengan error file sementara (TAR_ENTRY_ERROR).
     */
    private function runStep(string $command, string $base, array $env): \Illuminate\Process\ProcessResult
    {
        return Process::timeout(900)
            ->path($base)
            ->env($env)
            ->run(['bash', '-lc', $command]);
    }

    /** Daftar path yang bukan milik user web (penyebab umum update gagal). */
    private function permissionIssues(): array
    {
        $issues = [];
        $currentUid = function_exists('posix_geteuid') ? posix_geteuid() : getmyuid();

        foreach (['.git', 'node_modules', 'vendor', 'storage'] as $folder) {
            $path = base_path($folder);

            if (! is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                // Bandingkan UID lewat fileowner(); SplFileInfo::getOwner() bisa
                // mengembalikan nama user, sedangkan yang reliable adalah UID.
                if ((int) @fileowner($file->getPathname()) !== (int) $currentUid) {
                    $issues[] = $folder.'/'.trim(str_replace(base_path(), '', $file->getPathname()), '/');

                    if (count($issues) >= 6) {
                        return $issues;
                    }
                }
            }
        }

        return $issues;
    }

    /** Cek ulang ke GitHub (abaikan cache). */
    public function refresh()
    {
        $this->checker->forget();

        return back()->with('success', 'Status versi terbaru dicek ulang.');
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
        // Process berjalan sebagai user web (www-data): butuh HOME, cache, dan git config sendiri.
        $env = [
            'HOME' => storage_path('app'),
            'COMPOSER_HOME' => storage_path('app/composer'),
            'COMPOSER_ALLOW_SUPERUSER' => '1',
            'NPM_CONFIG_CACHE' => storage_path('app/npm-cache'),
            'GIT_TERMINAL_PROMPT' => '0',
            'GIT_CONFIG_GLOBAL' => storage_path('app/gitconfig'),
        ];

        $steps = [
            'Mengambil kode terbaru' => 'git pull --ff-only',
            'Memasang dependensi PHP' => 'COMPOSER_ALLOW_SUPERUSER=1 composer install --optimize-autoloader --no-interaction',
            'Memasang aset frontend' => 'npm ci --no-audit --no-fund --prefer-offline',
            'Membangun aset aplikasi' => 'npm run build',
            'Memperbarui database' => 'php artisan migrate --force',
            'Membersihkan cache' => 'php artisan optimize:clear',
        ];

        $log = [];

        try {
            $permissionIssues = $this->permissionIssues();
        } catch (\Throwable $e) {
            // Pemeriksaan izin tidak boleh menggagalkan pembaruan.
            $permissionIssues = [];
        }

        if ($permissionIssues !== []) {
            return view('updates.result', [
                'log' => [[
                    'step' => 'Pemeriksaan izin folder',
                    'ok' => false,
                    'output' => 'Ada file milik user lain yang tidak bisa diubah user web (www-data): '
                        .implode(', ', array_slice($permissionIssues, 0, 5))
                        ."\nJalankan: chown -R www-data:www-data ".base_path(),
                ]],
                'currentVersion' => $this->checker->currentVersion(),
                'latestVersion' => $this->checker->latestVersion(),
            ]);
        }

        foreach ($steps as $label => $command) {
            $result = $this->runStep($command, $base, $env);

            // npm kadang gagal sesaat (TAR_ENTRY_ERROR); coba sekali lagi.
            if (! $result->successful() && str_starts_with($command, 'npm ')) {
                $result = $this->runStep($command, $base, $env);
            }

            $output = trim($result->output().' '.$result->errorOutput());

            $log[] = [
                'step' => $label,
                'ok' => $result->successful(),
                'output' => $output,
            ];

            \Illuminate\Support\Facades\File::ensureDirectoryExists(storage_path('app/logs'));
            \Illuminate\Support\Facades\File::put(
                storage_path('app/logs/update-'.\Illuminate\Support\Str::slug($label).'.log'),
                $output
            );

            // Simpan detail tiap langkah ke log server untuk memudahkan diagnosa.
            \Illuminate\Support\Facades\Log::info('Pembaruan ['.$label.'] exit='.$result->exitCode(), [
                'potongan' => \Illuminate\Support\Str::limit($output, 1200),
            ]);

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