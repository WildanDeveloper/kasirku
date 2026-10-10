@php
    $money = fn ($value) => 'Rp ' . number_format((int) $value, 0, ',', '.');
@endphp

<div class="page-card p-6 space-y-5">
    <div class="text-center border-b border-gray-200 pb-4">
        <h3 class="text-lg font-bold text-gray-900">{{ $title }}</h3>
        <p class="text-sm text-gray-500">{{ config('kasirku.name') }} &middot; {{ $date->translatedFormat('l, d F Y') }}</p>
        <p class="text-[11px] text-gray-400">Dicetak {{ now()->format('d/m/Y H:i') }} oleh {{ auth()->user()?->name ?? '-' }}</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach ([
            'Transaksi' => number_format($data['transactions']).' trx',
            'Penjualan Kotor' => $money($data['gross']),
            'Diskon & Voucher' => $money($data['discount']),
            'Retur' => $money($data['returns']),
            'Pendapatan Bersih' => $money($data['net']),
            'HPP' => $money($data['cogs']),
            'Laba Kotor' => $money($data['profit']),
            'Penjualan Utang' => $money($data['credit']),
        ] as $label => $value)
            <div class="border border-gray-200 rounded-lg p-3">
                <div class="text-[11px] uppercase tracking-wide text-gray-500">{{ $label }}</div>
                <div class="mt-1 text-base font-bold text-gray-900">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div>
        <h4 class="section-title mb-2">Penjualan per Kasir</h4>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">Kasir</th>
                        <th class="table-th text-right">Transaksi</th>
                        <th class="table-th text-right">Penjualan</th>
                        <th class="table-th text-right">Tunai</th>
                        <th class="table-th text-right">Non-Tunai</th>
                        <th class="table-th text-right">Laba Kotor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($data['by_cashier'] as $row)
                        <tr>
                            <td class="table-td font-medium text-gray-900">{{ $row['user']->name }}</td>
                            <td class="table-td text-right">{{ $row['transactions'] }}</td>
                            <td class="table-td text-right font-semibold">{{ $money($row['revenue']) }}</td>
                            <td class="table-td text-right text-gray-600">{{ $money($row['cash']) }}</td>
                            <td class="table-td text-right text-gray-600">{{ $money($row['non_cash']) }}</td>
                            <td class="table-td text-right {{ $row['profit'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ $money($row['profit']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="table-td text-center text-gray-400 py-6">Tidak ada penjualan.</td></tr>
                    @endforelse
                </tbody>
                @if ($data['by_cashier']->isNotEmpty())
                    <tfoot class="bg-gray-50 font-semibold">
                        <tr>
                            <td class="table-td">Total</td>
                            <td class="table-td text-right">{{ number_format($data['transactions']) }}</td>
                            <td class="table-td text-right">{{ $money($data['gross'] - $data['discount']) }}</td>
                            <td class="table-td text-right">{{ $money($data['by_cashier']->sum('cash')) }}</td>
                            <td class="table-td text-right">{{ $money($data['by_cashier']->sum('non_cash')) }}</td>
                            <td class="table-td text-right">{{ $money($data['profit']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4 border-t border-gray-200 text-xs text-gray-500">
        <div class="text-center">
            <div class="h-16 border-b border-gray-300"></div>
            <div class="mt-1">Kasir</div>
        </div>
        <div class="text-center">
            <div class="h-16 border-b border-gray-300"></div>
            <div class="mt-1">Pemilik / Supervisor</div>
        </div>
        <div class="text-center">
            <div class="h-16 border-b border-gray-300"></div>
            <div class="mt-1">Tutup Kas (Z-Report)</div>
        </div>
    </div>
</div>