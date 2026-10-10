<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Pelanggan</h2>
            <p class="text-sm text-gray-500">Data pelanggan, harga grosir, dan riwayat utang.</p>
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
                                <th class="table-th text-center">Jenis</th>
                                <th class="table-th text-center">Transaksi</th>
                                <th class="table-th text-right">Sisa Utang</th>
                                <th class="table-th"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($customers as $customer)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="table-td">
                                        <a href="{{ route('customers.show', $customer) }}" class="font-semibold text-gray-900 hover:text-emerald-600 hover:underline">{{ $customer->name }}</a>
                                        <form method="POST" action="{{ route('customers.update', $customer) }}" class="mt-1.5 flex flex-wrap gap-2 items-center" x-data>
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="name" value="{{ $customer->name }}">
                                            <input type="hidden" name="phone" value="{{ $customer->phone }}">
                                            <input type="hidden" name="address" value="{{ $customer->address }}">
                                            <input type="hidden" name="is_wholesale" value="0">
                                            <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer">
                                                <input type="checkbox" name="is_wholesale" value="1" @checked($customer->is_wholesale)
                                                       class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                                Grosir
                                            </label>
                                            <button class="text-[10px] font-semibold text-emerald-600 hover:underline">Simpan</button>
                                        </form>
                                    </td>
                                    <td class="table-td text-gray-500">{{ $customer->phone ?? '—' }}</td>
                                    <td class="table-td text-center">
                                        <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $customer->is_wholesale ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $customer->is_wholesale ? 'Grosir' : 'Ecer' }}
                                        </span>
                                    </td>
                                    <td class="table-td text-center">{{ $customer->sales_count }}</td>
                                    <td class="table-td text-right">
                                        @php $sisa = $customer->outstanding(); @endphp
                                        @if ($sisa > 0)
                                            <span class="font-bold text-amber-600">Rp {{ number_format($sisa, 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="table-td text-right whitespace-nowrap">
                                        <a href="{{ route('customers.show', $customer) }}" class="text-sm font-semibold text-emerald-600 hover:text-emerald-500">Detail</a>
                                        <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="inline" onsubmit="return confirm('Hapus pelanggan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-sm font-semibold text-red-500 hover:text-red-700 ml-3">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-10 text-center text-sm text-gray-500">Belum ada pelanggan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($customers->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">
                        {{ $customers->links() }}
                    </div>
                @endif
            </div>

            <div class="page-card p-5">
                <h3 class="section-title mb-4">Tambah Pelanggan</h3>
                <form method="POST" action="{{ route('customers.store') }}" class="space-y-3">
                    @csrf
                    <input type="text" name="name" required placeholder="Nama pelanggan" class="form-input">
                    <input type="text" name="phone" placeholder="Telepon (opsional)" class="form-input">
                    <input type="text" name="address" placeholder="Alamat (opsional)" class="form-input">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="is_wholesale" value="1" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        Pelanggan grosir (pakai harga grosir)
                    </label>
                    <button class="w-full btn-primary">Simpan</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>