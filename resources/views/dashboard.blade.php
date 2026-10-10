<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900 leading-tight">Dashboard</h2>
                <p class="text-sm text-gray-500">{{ now()->translatedFormat('l, d F Y') }}</p>
            </div>
            @if ($openShift)
                <span class="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Shift aktif
                </span>
            @else
                <a href="{{ route('shifts.index') }}" class="btn-primary !py-1.5">Buka Shift</a>
            @endif
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8 space-y-5">
        @if (session('success'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <x-stat-card label="Omzet Hari Ini" value="Rp {{ number_format($todayRevenue, 0, ',', '.') }}"
                tone="emerald" icon="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            <x-stat-card label="Laba Hari Ini" value="Rp {{ number_format($todayProfit, 0, ',', '.') }}"
                tone="blue" icon="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
            <x-stat-card label="Transaksi Hari Ini" value="{{ number_format($todayTransactions) }} trx"
                tone="gray" icon="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
            <x-stat-card label="Piutang Belum Lunas" value="Rp {{ number_format($outstanding, 0, ',', '.') }}"
                tone="amber" icon="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
            <div class="lg:col-span-2 page-card p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="section-title">Omzet & Laba 7 Hari Terakhir</h3>
                    @if ($isAdmin)
                        <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-500">Laporan lengkap →</a>
                    @endif
                </div>

                @php
                    $maxValue = max(1, collect($last7)->max('revenue'));
                    $barWidth = 22;
                    $gap = 8;
                    $groupWidth = ($barWidth * 2) + $gap;
                    $chartWidth = $groupWidth * count($last7);
                    $chartHeight = 100;
                @endphp
                <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" class="w-full h-48" role="img"
                     aria-label="Grafik omzet dan laba 7 hari terakhir" preserveAspectRatio="none">
                    @foreach ($last7 as $index => $day)
                        @php
                            $x = $index * $groupWidth;
                            $revenueHeight = max(2, round($day['revenue'] / $maxValue * $chartHeight));
                            $profitHeight = max(2, round($day['profit'] / $maxValue * $chartHeight));
                        @endphp
                        <g>
                            <title>{{ $day['label'] }} — Omzet Rp {{ number_format($day['revenue'], 0, ',', '.') }}, Laba Rp {{ number_format($day['profit'], 0, ',', '.') }}</title>
                            <rect x="{{ $x }}" y="{{ $chartHeight - $revenueHeight }}" width="{{ $barWidth }}" height="{{ $revenueHeight }}"
                                  fill="#10b981" fill-opacity="0.8" rx="2" />
                            <rect x="{{ $x + $barWidth + $gap }}" y="{{ $chartHeight - $profitHeight }}" width="{{ $barWidth }}" height="{{ $profitHeight }}"
                                  fill="#60a5fa" fill-opacity="0.75" rx="2" />
                        </g>
                    @endforeach
                </svg>
                <div class="flex items-end gap-2 sm:gap-4 -mt-2">
                    @foreach ($last7 as $day)
                        <div class="flex-1 text-[10px] text-gray-500 text-center leading-tight">{{ $day['label'] }}</div>
                    @endforeach
                </div>
                <div class="flex items-center gap-4 mt-3 text-[11px] text-gray-500">
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded bg-emerald-500"></span> Omzet</span>
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded bg-blue-400"></span> Laba</span>
                </div>
            </div>

            <div class="page-card p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="section-title">Stok Menipis</h3>
                    @if ($isAdmin)
                        <a href="{{ route('products.index', ['search' => '']) }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-500">Semua produk →</a>
                    @endif
                </div>

                @forelse ($lowStock as $product)
                    <a href="{{ route('products.show', $product) }}" class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0 hover:bg-gray-50 rounded px-1 -mx-1">
                        <span class="text-sm text-gray-700 truncate">{{ $product->name }}</span>
                        <span class="text-xs font-bold {{ $product->stock <= 0 ? 'text-red-600' : 'text-amber-600' }} whitespace-nowrap ml-2">
                            {{ $product->stock }} {{ $product->unit }}
                        </span>
                    </a>
                @empty
                    <p class="text-sm text-gray-500 py-4 text-center">Semua stok aman 👍</p>
                @endforelse
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
            <div class="lg:col-span-2 page-card overflow-hidden">
                <div class="flex items-center justify-between p-5 border-b border-gray-100">
                    <h3 class="section-title">{{ $isAdmin ? 'Penjualan Terbaru' : 'Penjualan Saya' }}</h3>
                    <a href="{{ route('sales.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-500">Semua →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="table-th">Invoice</th>
                                <th class="table-th">Waktu</th>
                                <th class="table-th">Pelanggan</th>
                                <th class="table-th">Kasir</th>
                                <th class="table-th text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($recentSales as $sale)
                                <tr class="hover:bg-gray-50">
                                    <td class="table-td">
                                        <a href="{{ route('sales.show', $sale) }}" class="font-semibold text-emerald-700 hover:underline">{{ $sale->invoice_number }}</a>
                                    </td>
                                    <td class="table-td text-gray-500 whitespace-nowrap">{{ $sale->created_at->format('d/m H:i') }}</td>
                                    <td class="table-td text-gray-600">{{ $sale->customer?->name ?? 'Umum' }}</td>
                                    <td class="table-td text-gray-600">{{ $sale->user?->name }}</td>
                                    <td class="table-td text-right font-bold">Rp {{ number_format($sale->total, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-10 text-center text-sm text-gray-500">Belum ada penjualan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="page-card p-5">
                <h3 class="section-title mb-3">Ringkasan Bulan Ini</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Omzet</span><span class="font-semibold">Rp {{ number_format($monthRevenue, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Retur</span><span class="font-semibold text-red-600">- Rp {{ number_format($monthReturns, 0, ',', '.') }}</span></div>
                    @if ($isAdmin)
                        <div class="flex justify-between"><span class="text-gray-500">Pengeluaran</span><span class="font-semibold text-red-600">Rp {{ number_format($monthExpenses, 0, ',', '.') }}</span></div>
                    @endif
                    <div class="flex justify-between"><span class="text-gray-500">Jumlah produk</span><span class="font-semibold">{{ number_format($productCount) }}</span></div>
                </div>

                @if ($topProducts->isNotEmpty())
                    <h3 class="section-title mt-5 mb-2">Produk Terlaris</h3>
                    <div class="space-y-1.5 text-sm">
                        @foreach ($topProducts as $product)
                            <div class="flex justify-between">
                                <span class="text-gray-600 truncate">{{ $product->name }}</span>
                                <span class="text-xs font-semibold text-gray-400 ml-2 whitespace-nowrap">{{ $product->sold }} · Rp {{ number_format($product->revenue, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>