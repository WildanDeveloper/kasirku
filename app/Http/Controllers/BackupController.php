<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    private function backupDir(): string
    {
        return storage_path('app/backups');
    }

    public function index()
    {
        File::ensureDirectoryExists($this->backupDir());

        $backups = collect(File::files($this->backupDir()))
            ->sortByDesc(fn ($f) => $f->getFilename())
            ->map(fn ($f) => [
                'name' => $f->getFilename(),
                'size' => number_format($f->getSize() / 1024, 1).' KB',
                'created_at' => date('d/m/Y H:i', $f->getMTime()),
            ]);

        return view('settings.backup', compact('backups'));
    }

    public function store()
    {
        Artisan::call('kasirku:backup');

        return back()->with('success', 'Backup database dibuat.');
    }

    public function download(string $filename): BinaryFileResponse
    {
        $this->guardFilename($filename);

        return response()->download($this->backupDir().'/'.$filename);
    }

    public function restore(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:204800'],
        ]);

        $uploaded = $request->file('file');
        $temp = $this->backupDir().'/upload-'.now()->format('YmdHis').'.sqlite';

        File::ensureDirectoryExists($this->backupDir());
        $uploaded->move($this->backupDir(), basename($temp));

        // Validasi file benar-benar SQLite.
        $handle = fopen($temp, 'rb');
        $header = $handle ? fread($handle, 16) : '';
        if ($handle) {
            fclose($handle);
        }

        if (! str_starts_with($header, 'SQLite format 3')) {
            File::delete($temp);

            return back()->with('warning', 'File bukan database SQLite yang valid.');
        }

        // Simpan backup kondisi saat ini sebelum menimpa.
        Artisan::call('kasirku:backup');

        $database = config('database.connections.'.config('database.default').'.database');
        File::copy($temp, $database);
        File::delete($temp);

        DB::purge();

        ActivityLogger::record('backup.restored', 'Memulihkan database dari backup unggahan');

        return redirect()->route('settings.edit')->with('success', 'Database berhasil dipulihkan dari backup.');
    }

    public function destroy(string $filename)
    {
        $this->guardFilename($filename);
        File::delete($this->backupDir().'/'.$filename);

        return back()->with('success', 'Backup dihapus.');
    }

    private function guardFilename(string $filename): void
    {
        abort_unless(
            preg_match('/^kasirku-\d{8}-\d{6}\.sqlite$/', $filename),
            404,
            'Nama file backup tidak valid.'
        );
    }
}