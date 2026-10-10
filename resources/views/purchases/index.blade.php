<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Pembelian</h2>
            <p class="text-sm text-gray-500">Barang masuk dari supplier, harga beli, dan utang supplier.</p>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8 space-y-5">
        @if (session('success'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-lg bg-rose-50 border border-rose-100 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-stat-card label="Total Pembelian (filter)" :value="$purchases->sum('total')" />
            <x-stat-card label="Belum Dibayar" :value="$outstanding" tone="amber" />
            <x-stat-card label="Jumlah PO" :value="$purchases->total()" />
        </div>

        <div class="page-card p-5">
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                <div>
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-input">
                        <option value="">Semua</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="date" value="{{ request('date') }}" class="form-input">
                </div>
                <div class="flex gap-2">
                    <button class="btn-secondary">Filter</button>
                    <a href="{{ route('purchases.index') }}" class="btn-secondary">Reset</a>
                </div>
                <div class="flex gap-2 sm:justify-end">
                    <a href="{{ route('suppliers.index') }}" class="btn-secondary">Supplier</a>
                    <a href="{{ route('purchases.create') }}" class="btn-primary">+ Pembelian Baru</a>
                </div>
            </form>
        </div>

        <div class="page-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="table-th">No. PO</th>
                            <th class="table-th">Tanggal</th>
                            <th class="table-th">Supplier</th>
                            <th class="table-th text-right">Total</th>
                            <th class="table-th text-right">Dibayar</th>
                            <th class="table-th text-right">Sisa</th>
                            <th class="table-th">Status</th>
                            <th class="table-th text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($purchases as $purchase)
                            <tr class="hover:bg-gray-50">
                                <td class="table-td">
                                    <a href="{{ route('purchases.show', $purchase) }}" class="font-mono font-semibold text-blue-700 hover:underline">{{ $purchase->invoice_number }}</a>
                                </td>
                                <td class="table-td text-gray-600">{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                                <td class="table-td text-gray-600">{{ $purchase->supplier?->name ?? '-' }}</td>
                                <td class="table-td text-right font-semibold">{{ number_format($purchase->total, 0, ',', '.') }}</td>
                                <td class="table-td text-right text-emerald-700">{{ number_format($purchase->paid_amount, 0, ',', '.') }}</td>
                                <td class="table-td text-right {{ $purchase->outstanding() > 0 ? 'text-amber-700 font-semibold' : 'text-gray-400' }}">{{ number_format($purchase->outstanding(), 0, ',', '.') }}</td>
                                <td class="table-td">
                                    @if ($purchase->status === 'cancelled')
                                        <span class="badge bg-gray-100 text-gray-600">Dibatalkan</span>
                                    @elseif ($purchase->isPaid())
                                        <span class="badge bg-emerald-100 text-emerald-700">Lunas</span>
                                    @else
                                        <span class="badge bg-amber-100 text-amber-700">Belum Lunas</span>
                                    @endif
                                </td>
                                <td class="table-td text-right">
                                    <a href="{{ route('purchases.show', $purchase) }}" class="btn-ghost">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="table-td text-center text-gray-400 py-8">Belum ada pembelian.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $purchases->links() }}</div>
        </div>
    </div>
</x-app-layout>