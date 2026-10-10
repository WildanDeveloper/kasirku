<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Supplier</h2>
            <p class="text-sm text-gray-500">Daftar pemasok barang dan utang ke supplier.</p>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8 space-y-5">
        @if (session('success'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg bg-rose-50 border border-rose-100 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
            <div class="lg:col-span-2 page-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="table-th">Nama</th>
                                <th class="table-th">Kontak</th>
                                <th class="table-th text-right">Pembelian</th>
                                <th class="table-th text-right">Utang</th>
                                <th class="table-th text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($suppliers as $supplier)
                                <tr class="hover:bg-gray-50" x-data="{ editing: false }">
                                    <td class="table-td">
                                        <span x-show="!editing" class="font-semibold text-gray-900">{{ $supplier->name }}</span>
                                        <form x-show="editing" x-cloak method="POST" action="{{ route('suppliers.update', $supplier) }}" class="space-y-1">
                                            @csrf @method('PUT')
                                            <input type="text" name="name" value="{{ $supplier->name }}" class="form-input !py-1" required>
                                        </form>
                                    </td>
                                    <td class="table-td text-gray-600 text-xs">
                                        <span x-show="!editing">{{ $supplier->phone ?: '-' }}</span>
                                        <form x-show="editing" x-cloak method="POST" action="{{ route('suppliers.update', $supplier) }}" class="mt-1">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="name" value="{{ $supplier->name }}">
                                            <input type="text" name="phone" value="{{ $supplier->phone }}" class="form-input !py-1" placeholder="Telepon">
                                        </form>
                                    </td>
                                    <td class="table-td text-right">{{ $supplier->purchases_count }}</td>
                                    <td class="table-td text-right font-semibold {{ ($supplier->outstanding ?? 0) > 0 ? 'text-amber-700' : 'text-gray-400' }}">
                                        {{ number_format($supplier->outstanding ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="table-td text-right">
                                        <button type="button" @click="editing = !editing" class="btn-ghost" x-text="editing ? 'Batal' : 'Edit'"></button>
                                        <form x-show="editing" x-cloak method="POST" action="{{ route('suppliers.update', $supplier) }}" class="inline">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="name" value="{{ $supplier->name }}">
                                            <button class="btn-primary !py-1.5">Simpan</button>
                                        </form>
                                        <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="inline"
                                              onsubmit="return confirm('Hapus supplier ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn-ghost !text-rose-600" x-show="!editing">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="table-td text-center text-gray-400 py-8">Belum ada supplier.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 flex gap-3">
                    <a href="{{ route('purchases.index') }}" class="btn-secondary">← Daftar Pembelian</a>
                </div>
            </div>

            <div class="page-card p-5">
                <h3 class="section-title mb-3">Tambah Supplier</h3>
                <form method="POST" action="{{ route('suppliers.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Telepon</label>
                        <input type="text" name="phone" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Alamat</label>
                        <textarea name="address" rows="2" class="form-input"></textarea>
                    </div>
                    <div>
                        <label class="form-label">Catatan</label>
                        <textarea name="note" rows="2" class="form-input"></textarea>
                    </div>
                    <button class="btn-primary w-full">Simpan</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>