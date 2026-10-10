<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('customers.index') }}" class="text-xs font-semibold text-emerald-600 hover:underline">← Pelanggan</a>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">{{ $customer->name }}</h2>
            <p class="text-sm text-gray-500">
                {{ $customer->phone ?? 'Tanpa HP' }}
                @if ($customer->address) · {{ $customer->address }} @endif
                @if ($customer->is_wholesale) · <span class="font-semibold text-blue-600">Pelanggan Grosir</span> @endif
            </p>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8 space-y-5">
        @if (session('success'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <x-stat-card label="Transaksi" :value="number_format($stats['transactions']).'x'" tone="gray"
                icon="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
            <x-stat-card label="Total Belanja" :value="'Rp '.number_format($stats['total_spent'], 0, ',', '.')" tone="emerald"
                icon="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            <x-stat-card label="Rata-rata Belanja" :value="'Rp '.number_format($stats['avg'], 0, ',', '.')" tone="blue"
                icon="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
            <x-stat-card label="Sisa Utang" :value="'Rp '.number_format($stats['outstanding'], 0, ',', '.')" :tone="$stats['outstanding'] > 0 ? 'amber' : 'gray'"
                icon="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
            <div class="lg:col-span-2 page-card overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="section-title">Riwayat Pembelian</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="table-th">Invoice</th>
                                <th class="table-th">Waktu</th>
                                <th class="table-th">Kasir</th>
                                <th class="table-th">Status</th>
                                <th class="table-th text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($sales as $sale)
                                <tr class="hover:bg-gray-50">
                                    <td class="table-td">
                                        <a href="{{ route('sales.show', $sale) }}" class="font-semibold text-emerald-700 hover:underline">{{ $sale->invoice_number }}</a>
                                    </td>
                                    <td class="table-td text-gray-500 whitespace-nowrap">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="table-td text-gray-600">{{ $sale->user?->name }}</td>
                                    <td class="table-td">
                                        @if ($sale->is_credit && ! $sale->settled_at)
                                            <span class="text-xs font-semibold text-amber-600">Utang Rp {{ number_format($sale->remainingAmount(), 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-xs font-semibold text-emerald-600">Lunas</span>
                                        @endif
                                    </td>
                                    <td class="table-td text-right font-semibold">{{ number_format($sale->total, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-10 text-center text-sm text-gray-500">Belum ada pembelian.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($sales->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">{{ $sales->links() }}</div>
                @endif
            </div>

            <div class="space-y-5">
                @if ($favorites->isNotEmpty())
                    <div class="page-card p-5">
                        <h3 class="section-title mb-3">Produk Favorit</h3>
                        <div class="space-y-2 text-sm">
                            @foreach ($favorites as $favorite)
                                <div class="flex justify-between">
                                    <span class="text-gray-600 truncate">{{ $favorite->name }}</span>
                                    <span class="text-xs font-semibold text-gray-400 ml-2 whitespace-nowrap">{{ $favorite->qty }} · Rp {{ number_format($favorite->spent, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="page-card p-5">
                    <h3 class="section-title mb-1">Harga Khusus</h3>
                    <p class="text-xs text-gray-500 mb-3">Harga khusus pelanggan ini dipakai otomatis di POS, menggantikan harga grosir.</p>

                    <form method="POST" action="{{ route('customers.prices.store', $customer) }}" class="flex gap-2 mb-3">
                        @csrf
                        <input type="number" name="product_id" class="form-input" placeholder="ID produk" min="1" required>
                        <input type="number" name="price" class="form-input w-28" placeholder="Harga" min="0" required>
                        <button class="btn-primary !px-3">+</button>
                    </form>

                    <div class="space-y-1.5 text-sm">
                        @forelse ($prices as $cp)
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 truncate">{{ $cp->product->name ?? 'Produk #'.$cp->product_id }}</span>
                                <span class="flex items-center gap-2 ml-2">
                                    <span class="font-semibold text-blue-600">Rp {{ number_format($cp->price, 0, ',', '.') }}</span>
                                    <form method="POST" action="{{ route('customers.prices.destroy', [$customer, $cp]) }}" onsubmit="return confirm('Hapus harga khusus ini?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-500 hover:text-red-700 text-xs font-bold">&times;</button>
                                    </form>
                                </span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Belum ada harga khusus.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>