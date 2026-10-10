<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Penjualan</h2>
            <p class="text-sm text-gray-500">Riwayat transaksi penjualan.</p>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        @if ($outstanding > 0 && auth()->user()->is_admin)
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wide text-amber-700">Total Piutang Belum Lunas</span>
                    <span class="block text-xl font-extrabold text-amber-800">Rp {{ number_format($outstanding, 0, ',', '.') }}</span>
                </div>
                <a href="{{ request()->fullUrlWithQuery(['credit' => '1']) }}"
                   class="btn-secondary !border-amber-300 !text-amber-800">Lihat Transaksi Utang</a>
            </div>
        @endif

        <div class="page-card overflow-hidden">
            <div class="p-4 border-b border-gray-100">
                <form method="GET" class="flex gap-2">
                    <input type="date" name="date" value="{{ request('date') }}" class="form-input sm:w-48">
                    <button class="btn-secondary">Filter</button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="table-th">No. Invoice</th>
                            <th class="table-th">Waktu</th>
                            <th class="table-th">Pelanggan</th>
                            <th class="table-th">Kasir</th>
                            <th class="table-th">Metode</th>
                            <th class="table-th text-right">Total</th>
                            <th class="table-th"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($sales as $sale)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="table-td font-semibold text-gray-900">{{ $sale->invoice_number }}</td>
                                <td class="table-td whitespace-nowrap text-gray-500">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                                <td class="table-td">{{ $sale->customer?->name ?? '—' }}</td>
                                <td class="table-td">{{ $sale->user?->name ?? '—' }}</td>
                                <td class="table-td">
                                    @php $methodColors = ['cash' => 'bg-emerald-100 text-emerald-700', 'qris' => 'bg-purple-100 text-purple-700', 'debit' => 'bg-blue-100 text-blue-700']; @endphp
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $methodColors[$sale->payment_method] ?? 'bg-gray-100 text-gray-600' }}">
                                        {{ strtoupper($sale->payment_method) }}
                                    </span>
                                </td>
                                <td class="table-td text-right">
                                    @if ($sale->discount > 0)
                                        <span class="text-xs text-amber-600 font-semibold">-Rp {{ number_format($sale->discount, 0, ',', '.') }}</span><br>
                                    @endif
                                    <span class="font-bold">Rp {{ number_format($sale->total, 0, ',', '.') }}</span>
                                    @if ($sale->is_credit && ! $sale->settled_at)
                                        <span class="block text-[10px] uppercase font-bold text-amber-600">Utang: Rp {{ number_format($sale->remainingAmount(), 0, ',', '.') }}</span>
                                    @endif
                                    @if ($sale->returns->isNotEmpty())
                                        <span class="block text-[10px] uppercase font-bold text-red-500">Retur: Rp {{ number_format($sale->returns->sum('total'), 0, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="table-td text-right">
                                    <a href="{{ route('sales.show', $sale) }}"
                                       class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-600 hover:text-emerald-500">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                        </svg>
                                        Struk
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center">
                                    <p class="text-sm text-gray-500">Belum ada penjualan.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($sales->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">
                    {{ $sales->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
