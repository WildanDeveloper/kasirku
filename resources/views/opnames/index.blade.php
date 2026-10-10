<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Stok Opname</h2>
            <p class="text-sm text-gray-500">Hitung fisik barang, selisih otomatis adjusts ke stok.</p>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8 space-y-5">
        @if (session('success'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif
        @if (session('warning'))
            <div class="rounded-lg bg-amber-50 border border-amber-100 px-4 py-3 text-sm text-amber-700">{{ session('warning') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg bg-rose-50 border border-rose-100 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
            <div class="lg:col-span-2 page-card overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="section-title">Riwayat Opname</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="table-th">Nama</th>
                                <th class="table-th">Tanggal</th>
                                <th class="table-th text-right">Produk</th>
                                <th class="table-th text-right">Selisih</th>
                                <th class="table-th">Status</th>
                                <th class="table-th text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($opnames as $opname)
                                <tr class="hover:bg-gray-50">
                                    <td class="table-td font-semibold text-gray-900">{{ $opname->name }}</td>
                                    <td class="table-td text-gray-600 text-xs">
                                        {{ ($opname->finished_at ?? $opname->created_at)->format('d/m/Y H:i') }}
                                        <div class="text-gray-400">{{ $opname->user?->name ?? '-' }}</div>
                                    </td>
                                    <td class="table-td text-right">{{ $opname->items_count }}</td>
                                    <td class="table-td text-right font-semibold {{ $opname->difference_value < 0 ? 'text-rose-700' : ($opname->difference_value > 0 ? 'text-emerald-700' : 'text-gray-400') }}">
                                        {{ number_format($opname->difference_value, 0, ',', '.') }}
                                    </td>
                                    <td class="table-td">
                                        @if ($opname->isFinished())
                                            <span class="badge bg-emerald-100 text-emerald-700">Selesai</span>
                                        @else
                                            <span class="badge bg-amber-100 text-amber-700">Berjalan</span>
                                        @endif
                                    </td>
                                    <td class="table-td text-right">
                                        <a href="{{ route('opnames.show', $opname) }}" class="btn-ghost">{{ $opname->isFinished() ? 'Lihat' : 'Lanjut Hitung' }}</a>
                                        @unless ($opname->isFinished())
                                            <form method="POST" action="{{ route('opnames.destroy', $opname) }}" class="inline" onsubmit="return confirm('Hapus opname ini?')">
                                                @csrf @method('DELETE')
                                                <button class="btn-ghost !text-rose-600">Hapus</button>
                                            </form>
                                        @endunless
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="table-td text-center text-gray-400 py-8">Belum ada opname.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">{{ $opnames->links() }}</div>
            </div>

            <div class="page-card p-5">
                <h3 class="section-title mb-3">Mulai Opname Baru</h3>
                <form method="POST" action="{{ route('opnames.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" value="{{ old('name', 'Opname ' . now()->format('d/m/Y H:i')) }}" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Kategori (opsional)</label>
                        <select name="category_id" class="form-input">
                            <option value="">Semua kategori</option>
                            @foreach (\App\Models\Category::orderBy('name')->get() as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="only_low" value="1" class="rounded border-gray-300">
                        Hanya produk dengan stok menipis
                    </label>
                    <div>
                        <label class="form-label">Catatan</label>
                        <textarea name="note" rows="2" class="form-input"></textarea>
                    </div>
                    <button class="btn-primary w-full">Buat &amp; Mulai Hitung</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>