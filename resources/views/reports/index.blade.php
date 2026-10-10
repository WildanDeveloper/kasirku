<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-gray-900 leading-tight">Laporan</h2>
                <p class="text-sm text-gray-500">Ringkasan {{ $start->format('d/m/Y') }} — {{ $end->format('d/m/Y') }}</p>
            </div>
            <a href="{{ route('reports.export', request()->only(['start_date', 'end_date'])) }}" class="btn-secondary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Export CSV
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <form method="GET" class="flex flex-wrap gap-2 mb-6">
            <input type="date" name="start_date" value="{{ $start->format('Y-m-d') }}" class="form-input sm:w-44">
            <input type="date" name="end_date" value="{{ $end->format('Y-m-d') }}" class="form-input sm:w-44">
            <button class="btn-secondary">Terapkan</button>
        </form>

        <!-- Ringkasan keuangan -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <x-stat-card label="Jumlah Transaksi" :value="number_format($summary['transactions'])" tone="blue" icon="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
            <x-stat-card label="Pendapatan Kotor" value="Rp {{ number_format($summary['gross'], 0, ',', '.') }}" tone="gray" icon="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            <x-stat-card label="Diskon Diberikan" value="Rp {{ number_format($summary['discount'], 0, ',', '.') }}" tone="amber" icon="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z M6 6h.008v.008H6V6Z" />
            <x-stat-card label="Retur Penjualan" value="Rp {{ number_format($summary['returns'], 0, ',', '.') }}" tone="red" icon="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
            <x-stat-card label="Pendapatan Bersih" value="Rp {{ number_format($summary['net'], 0, ',', '.') }}" tone="emerald" icon="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <x-stat-card label="HPP (Modal Terjual)" value="Rp {{ number_format($summary['cogs'], 0, ',', '.') }}" tone="gray" icon="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
            <x-stat-card label="Laba Kotor" value="Rp {{ number_format($summary['gross_profit'], 0, ',', '.') }}" tone="blue" icon="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
            <x-stat-card label="Pengeluaran" value="Rp {{ number_format($summary['expenses'], 0, ',', '.') }}" tone="red" icon="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            <x-stat-card label="Laba Bersih" value="Rp {{ number_format($summary['net_profit'], 0, ',', '.') }}" tone="{{ $summary['net_profit'] >= 0 ? 'emerald' : 'red' }}" icon="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="page-card p-5">
                <h3 class="section-title mb-4">Penjualan Harian</h3>
                @if ($daily->isEmpty())
                    <p class="py-8 text-center text-sm text-gray-500">Tidak ada data.</p>
                @else
                    @php
                        $days = $daily->sortBy('day')->values();
                        $maxRevenue = $daily->max('revenue') ?: 1;
                        $barWidth = 18;
                        $gap = 8;
                        $groupWidth = $barWidth + $gap;
                        $chartWidth = max(1, $groupWidth * $days->count());
                        $chartHeight = 100;
                    @endphp
                    @if ($days->isNotEmpty())
                        <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" class="w-full h-40 mb-2" role="img"
                             aria-label="Grafik omzet harian" preserveAspectRatio="none">
                            @foreach ($days as $index => $day)
                                @php
                                    $barHeight = max(round($day->revenue / $maxRevenue * $chartHeight), 4);
                                    $x = $index * $groupWidth;
                                @endphp
                                <g>
                                    <title>{{ \Carbon\Carbon::parse($day->day)->format('d/m/Y') }}: Rp {{ number_format($day->revenue, 0, ',', '.') }}</title>
                                    <rect x="{{ $x }}" y="{{ $chartHeight - $barHeight }}" width="{{ $barWidth }}" height="{{ $barHeight }}"
                                          fill="#10b981" fill-opacity="0.8" rx="2" />
                                </g>
                            @endforeach
                        </svg>
                        <div class="flex -mt-2">
                            @foreach ($days as $day)
                                <div class="flex-1 text-[10px] text-gray-400 text-center">{{ \Carbon\Carbon::parse($day->day)->format('d/m') }}</div>
                            @endforeach
                        </div>
                    @endif
                    <div class="overflow-x-auto border-t border-gray-100 pt-3">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                                    <th class="py-1.5">Tanggal</th>
                                    <th class="py-1.5 text-right">Trx</th>
                                    <th class="py-1.5 text-right">Kotor</th>
                                    <th class="py-1.5 text-right">Retur</th>
                                    <th class="py-1.5 text-right">Bersih</th>
                                    <th class="py-1.5 text-right">Laba</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach ($daily as $day)
                                    <tr>
                                        <td class="py-1.5 text-gray-600">{{ \Carbon\Carbon::parse($day->day)->translatedFormat('d M Y') }}</td>
                                        <td class="py-1.5 text-right">{{ $day->transactions }}</td>
                                        <td class="py-1.5 text-right text-gray-500">Rp {{ number_format($day->gross, 0, ',', '.') }}</td>
                                        <td class="py-1.5 text-right text-red-500">@if ($day->returns > 0)- Rp {{ number_format($day->returns, 0, ',', '.') }}@else —@endif</td>
                                        <td class="py-1.5 text-right font-semibold">Rp {{ number_format($day->revenue, 0, ',', '.') }}</td>
                                        <td class="py-1.5 text-right font-semibold {{ $day->profit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">Rp {{ number_format($day->profit, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="page-card overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="section-title">Produk Terlaris</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="table-th">Produk</th>
                                <th class="table-th text-right">Terjual</th>
                                <th class="table-th text-right">Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($topProducts as $product)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="table-td font-semibold text-gray-900">{{ $product->name }}</td>
                                    <td class="table-td text-right">{{ $product->sold }}</td>
                                    <td class="table-td text-right font-semibold">Rp {{ number_format($product->revenue, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-10 text-center text-sm text-gray-500">Tidak ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
