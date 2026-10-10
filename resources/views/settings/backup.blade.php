<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <a href="{{ route('settings.edit') }}" class="text-xs font-semibold text-emerald-600 hover:underline">← Pengaturan</a>
                <h2 class="text-xl font-bold text-gray-900 leading-tight">Backup Database</h2>
                <p class="text-sm text-gray-500">Unduh cadangan, pulihkan, atau jadwalkan backup harian.</p>
            </div>
            <form method="POST" action="{{ route('settings.backup.store') }}">
                @csrf
                <button class="btn-primary">Backup Sekarang</button>
            </form>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 py-6 sm:px-6 lg:px-8 space-y-5">
        @if (session('success'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif
        @if (session('warning'))
            <div class="rounded-lg bg-amber-50 border border-amber-100 px-4 py-3 text-sm text-amber-700">{{ session('warning') }}</div>
        @endif

        <div class="page-card overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h3 class="section-title">Backup Tersimpan ({{ $backups->count() }})</h3>
                <p class="text-xs text-gray-500 mt-1">Disimpan di server pada <code class="font-mono">storage/app/backups</code>.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="table-th">File</th>
                            <th class="table-th">Dibuat</th>
                            <th class="table-th text-right">Ukuran</th>
                            <th class="table-th text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($backups as $backup)
                            <tr class="hover:bg-gray-50">
                                <td class="table-td font-mono text-xs">{{ $backup['name'] }}</td>
                                <td class="table-td text-gray-500">{{ $backup['created_at'] }}</td>
                                <td class="table-td text-right">{{ $backup['size'] }}</td>
                                <td class="table-td text-right whitespace-nowrap">
                                    <a href="{{ route('settings.backup.download', $backup['name']) }}" class="btn-secondary !py-1 !px-2 text-xs">Unduh</a>
                                    <form method="POST" action="{{ route('settings.backup.destroy', $backup['name']) }}" class="inline"
                                          onsubmit="return confirm('Hapus file backup ini?')">
                                        @csrf @method('DELETE')
                                        <button class="text-xs font-semibold text-red-500 hover:text-red-700 ml-2">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-10 text-center text-sm text-gray-500">Belum ada backup. Klik "Backup Sekarang".</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="page-card p-5">
            <h3 class="section-title mb-1">Pulihkan dari File Backup</h3>
            <p class="text-xs text-gray-500 mb-4">Unggah file <code class="font-mono">.sqlite</code>. Sistem otomatis membuat backup kondisi saat ini sebelum menimpa.</p>

            <form method="POST" action="{{ route('settings.backup.restore') }}" enctype="multipart/form-data"
                  class="flex flex-col sm:flex-row gap-3 items-start"
                  x-data
                  @submit="if (! confirm('Pulihkan database? Semua data saat ini akan diganti!')) $event.preventDefault()">
                @csrf
                <input type="file" name="file" accept=".sqlite,application/vnd.sqlite3" required class="form-input">
                <button class="btn-danger whitespace-nowrap">Pulihkan Database</button>
            </form>
        </div>

        <div class="page-card p-5">
            <h3 class="section-title mb-2">Backup Otomatis Harian</h3>
            <p class="text-sm text-gray-600 mb-3">
                Tambahkan cron di server agar backup dibuat tiap malam, misalnya pukul 02:00:
            </p>
            <pre class="bg-slate-900 text-slate-100 text-xs rounded-lg p-4 overflow-x-auto">0 2 * * * cd {{ base_path() }} &amp;&amp; php artisan kasirku:backup --keep=14 &gt;&gt;/dev/null 2&gt;&amp;1</pre>
            <p class="text-xs text-gray-500 mt-2">14 backup terakhir disimpan, sisanya dihapus otomatis.</p>
        </div>
    </div>
</x-app-layout>