<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Pelanggan</h2>
            <p class="text-sm text-gray-500">Data pelanggan untuk transaksi.</p>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 flex items-center gap-2 rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div class="lg:col-span-2 page-card overflow-hidden">
                <div class="p-4 border-b border-gray-100">
                    <form method="GET" class="flex gap-2">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / telepon..." class="form-input flex-1">
                        <button class="btn-secondary">Cari</button>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="table-th">Nama</th>
                                <th class="table-th">Telepon</th>
                                <th class="table-th text-center">Transaksi</th>
                                <th class="table-th"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($customers as $customer)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="table-td">
                                        <form method="POST" action="{{ route('customers.update', $customer) }}" class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center" x-data>
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="name" value="{{ $customer->name }}" required class="form-input sm:w-44">
                                            <input type="text" name="phone" value="{{ $customer->phone }}" placeholder="Telepon" class="form-input sm:w-36">
                                            <input type="text" name="address" value="{{ $customer->address }}" placeholder="Alamat" class="form-input sm:w-40 hidden sm:block">
                                            <button class="btn-secondary !px-3 !py-1.5">Simpan</button>
                                        </form>
                                    </td>
                                    <td class="table-td text-gray-500">{{ $customer->phone ?? '—' }}</td>
                                    <td class="table-td text-center">{{ $customer->sales_count }}</td>
                                    <td class="table-td text-right">
                                        <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Hapus pelanggan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-sm font-semibold text-red-500 hover:text-red-700">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-10 text-center text-sm text-gray-500">Belum ada pelanggan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($customers->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">{{ $customers->links() }}</div>
                @endif
            </div>

            <div class="page-card p-5">
                <h3 class="section-title mb-4">Tambah Pelanggan</h3>
                <form method="POST" action="{{ route('customers.store') }}" class="space-y-3">
                    @csrf
                    <input type="text" name="name" required placeholder="Nama pelanggan" class="form-input">
                    <input type="text" name="phone" placeholder="Telepon (opsional)" class="form-input">
                    <input type="text" name="address" placeholder="Alamat (opsional)" class="form-input">
                    <button class="w-full btn-primary">Simpan</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
