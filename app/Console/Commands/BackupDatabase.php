<?php

namespace App\Console\Commands;

use App\Services\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    protected $signature = 'kasirku:backup {--keep=14 : jumlah backup lama yang disimpan} {--path= : sumber database (default dari config)}';

    protected $description = 'Mencadangkan database SQLite ke storage/app/backups';

    public function handle(): int
    {
        $database = $this->option('path') ?: config('database.connections.'.config('database.default').'.database');

        if (! is_file($database)) {
            $this->error('Database tidak ditemukan: '.$database);

            return self::FAILURE;
        }

        $keep = max(1, (int) $this->option('keep'));
        $directory = storage_path('app/backups');

        File::ensureDirectoryExists($directory);

        $filename = 'kasirku-'.now()->format('Ymd-His').'.sqlite';
        $target = $directory.'/'.$filename;

        // Salin aman: checkpoint dulu supaya tidak menulis file setengah jadi.
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo()->exec('PRAGMA wal_checkpoint(FULL)');
        } catch (\Throwable $e) {
            // Database in-memory / non-SQLite: lewati checkpoint.
        }

        if (! copy($database, $target)) {
            $this->error('Gagal menyalin database.');

            return self::FAILURE;
        }

        // Backup rusak lebih berbahaya daripada tidak ada backup sama sekali:
        // verifikasi file hasil salinan sebelum dianggap berhasil.
        if (! $this->isValidSqlite($target)) {
            File::delete($target);
            $this->error('Salinan database tidak valid, backup dibatalkan.');

            return self::FAILURE;
        }

        // Buang backup lama.
        $files = collect(File::files($directory))
            ->filter(fn ($f) => $f->getFilename() !== $filename)
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->skip($keep - 1);

        foreach ($files as $file) {
            File::delete($file->getPathname());
        }

        ActivityLogger::record('backup.created', 'Backup database '.$filename);

        $this->info('Backup dibuat: '.$target.' ('.number_format(File::size($target) / 1024, 1).' KB)');

        return self::SUCCESS;
    }

    /**
     * Cek cepat bahwa file benar-benar database SQLite yang bisa dibaca:
     * header benar dan bisa opened PDO tanpa error.
     */
    private function isValidSqlite(string $path): bool
    {
        if (! str_starts_with((string) file_get_contents($path, false, null, 0, 16), 'SQLite format 3')) {
            return false;
        }

        try {
            new \PDO('sqlite:'.$path)->query('SELECT count(*) FROM sqlite_master')->fetch();

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}