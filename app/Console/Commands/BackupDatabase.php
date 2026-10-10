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
}