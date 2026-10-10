<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <a href="{{ route('products.index') }}" class="text-xs font-semibold text-emerald-600 hover:underline">← Produk</a>
                <h2 class="text-xl font-bold text-gray-900 leading-tight">{{ $product->name }}</h2>
                <p class="text-sm text-gray-500">
                    {{ $product->category?->name ?? 'Tanpa kategori' }}
                    @if ($product->barcode) · {{ $product->barcode }} @endif
                </p>
            </div>
            @if (auth()->user()->is_admin)
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('products.labels') }}">
                        @csrf
                        <input type="hidden" name="ids[]" value="{{ $product->id }}">
                        <input type="hidden" name="mode" value="retail">
                        <button class="btn-secondary">Cetak Label</button>
                    </form>
                    <a href="{{ route('products.edit', $product) }}" class="btn-primary">Edit</a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8 space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <x-stat-card label="Stok" :value="$product->stock.' '.$product->unit" :tone="$product->isLowStock() ? 'red' : 'emerald'"
                icon="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
            <x-stat-card label="Terjual" :value="number_format($sold).' '.$product->unit" tone="blue"
                icon="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
            <x-stat-card label="Omzet Produk" :value="'Rp '.number_format($revenue, 0, ',', '.')" tone="gray"
                icon="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            @if (auth()->user()->is_admin)
                <x-stat-card label="Potensi Laba" :value="'Rp '.number_format($potentialProfit, 0, ',', '.')" tone="emerald"
                    icon="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
            @endif
        </div>

        @if ($returned > 0)
            <div class="rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700">
                Ada {{ $returned }} {{ $product->unit }} produk ini yang diretur pelanggan.
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
            <div class="page-card p-5">
                <h3 class="section-title mb-3">Informasi Harga</h3>
                <div class="space-y-2 text-sm">
                    @if (auth()->user()->is_admin)
                        <div class="flex justify-between"><span class="text-gray-500">Harga Modal</span><span class="font-semibold">Rp {{ number_format($product->cost_price, 0, ',', '.') }}</span></div>
                    @endif
                    <div class="flex justify-between"><span class="text-gray-500">Harga Ecer</span><span class="font-semibold text-emerald-700">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Harga Grosir</span>
                        <span class="font-semibold">Rp {{ number_format($product->wholesalePrice(), 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Stok Minimum</span>
                        <span class="font-semibold">{{ $product->min_stock }} {{ $product->unit }}</span>
                    </div>
                </div>

                @if ($product->customerPrices->isNotEmpty())
                    <h3 class="section-title mt-5 mb-2">Harga Khusus Pelanggan</h3>
                    <div class="space-y-1.5 text-sm">
                        @foreach ($product->customerPrices as $cp)
                            <div class="flex justify-between">
                                <span class="text-gray-600">{{ $cp->customer->name }}</span>
                                <span class="font-semibold text-blue-600">Rp {{ number_format($cp->price, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="lg:col-span-2 page-card overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="section-title">Penjualan Terakhir</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="table-th">Waktu</th>
                                <th class="table-th">Invoice</th>
                                <th class="table-th">Pelanggan</th>
                                <th class="table-th text-right">Qty</th>
                                <th class="table-th text-right">Harga</th>
                                <th class="table-th text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($recentSales as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="table-td text-gray-500 whitespace-nowrap">{{ $item->sale->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="table-td">
                                        <a href="{{ route('sales.show', $item->sale) }}" class="font-semibold text-emerald-700 hover:underline">{{ $item->sale->invoice_number }}</a>
                                    </td>
                                    <td class="table-td text-gray-600">{{ $item->sale->customer?->name ?? 'Umum' }}</td>
                                    <td class="table-td text-right">{{ $item->quantity }}</td>
                                    <td class="table-td text-right">{{ number_format($item->price, 0, ',', '.') }}</td>
                                    <td class="table-td text-right font-semibold">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-10 text-center text-sm text-gray-500">Belum ada penjualan produk ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="page-card overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h3 class="section-title">Riwayat Stok (20 terakhir)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="table-th">Waktu</th>
                            <th class="table-th">Jenis</th>
                            <th class="table-th text-right">Jumlah</th>
                            <th class="table-th text-right">Stok Akhir</th>
                            <th class="table-th">Keterangan</th>
                            <th class="table-th">Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($movements as $movement)
                            <tr class="hover:bg-gray-50">
                                <td class="table-td text-gray-500 whitespace-nowrap">{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                                <td class="table-td">
                                    <span class="text-xs font-semibold uppercase {{ $movement->type === 'out' ? 'text-red-600' : ($movement->type === 'adjustment' ? 'text-amber-600' : 'text-emerald-600') }}">{{ $movement->type }}</span>
                                </td>
                                <td class="table-td text-right font-semibold">{{ $movement->type === 'out' ? '-' : ($movement->type === 'adjustment' ? '=' : '+') }}{{ $movement->quantity }}</td>
                                <td class="table-td text-right">{{ $movement->stock_after }}</td>
                                <td class="table-td text-gray-600">{{ $movement->note }}</td>
                                <td class="table-td text-gray-600">{{ $movement->user?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-10 text-center text-sm text-gray-500">Belum ada mutasi stok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>