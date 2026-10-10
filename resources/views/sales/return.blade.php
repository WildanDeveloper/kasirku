<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('sales.show', $sale) }}" class="text-xs font-semibold text-emerald-600 hover:underline">← {{ $sale->invoice_number }}</a>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Retur Barang</h2>
            <p class="text-sm text-gray-500">
                {{ $sale->created_at->format('d/m/Y H:i') }} ·
                {{ $sale->customer?->name ?? 'Pelanggan Umum' }} ·
                Total Rp {{ number_format($sale->total, 0, ',', '.') }}
            </p>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('sales.return.store', $sale) }}" class="space-y-5" x-data="{ method: 'cash' }">
            @csrf

            <div class="page-card overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="section-title">Pilih Barang yang Diretur</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Isi jumlah yang diretur (0 = tidak diretur). Maksimal sesuai jumlah yang dibeli.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="table-th">Produk</th>
                                <th class="table-th text-right">Harga</th>
                                <th class="table-th text-right">Dibeli</th>
                                <th class="table-th text-right">Sudah Retur</th>
                                <th class="table-th text-right w-32">Retur</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($sale->returnableItems() as $row)
                                <tr>
                                    <td class="table-td font-medium text-gray-800">{{ $row->item->product->name }}</td>
                                    <td class="table-td text-right">{{ number_format($row->item->price, 0, ',', '.') }}</td>
                                    <td class="table-td text-right">{{ $row->item->quantity }}</td>
                                    <td class="table-td text-right text-gray-500">{{ $row->returned }}</td>
                                    <td class="table-td text-right">
                                        <input type="number" name="items[{{ $loop->index }}][sale_item_id]" value="{{ $row->item->id }}" class="hidden">
                                        <input type="number" name="items[{{ $loop->index }}][quantity]" min="0" max="{{ $row->available }}"
                                               class="form-input !py-1 text-right" placeholder="0">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="page-card p-5 space-y-4">
                <div>
                    <x-input-label value="Metode Pengembalian" />
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2">
                        <label class="flex items-start gap-2 rounded-lg border p-3 cursor-pointer" :class="method === 'cash' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200'">
                            <input type="radio" value="cash" x-model="method" name="refund_method" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm">
                                <span class="block font-semibold text-gray-800">Kembalikan uang</span>
                                <span class="block text-xs text-gray-500">
                                    @if ($sale->is_credit)
                                        Transaksi utang: retur otomatis mengurangi sisa utang.
                                    @else
                                        Uang dikembalikan ke pelanggan sesuai nilai retur.
                                    @endif
                                </span>
                            </span>
                        </label>
                        <label class="flex items-start gap-2 rounded-lg border p-3 cursor-pointer" :class="method === 'restock' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200'">
                            <input type="radio" value="restock" x-model="method" name="refund_method" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm">
                                <span class="block font-semibold text-gray-800">Barang saja (tanpa uang)</span>
                                <span class="block text-xs text-gray-500">Barang kembali ke stok, pelanggan tidak menerima refund.</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="reason" value="Alasan Retur" />
                        <input id="reason" name="reason" type="text" maxlength="100" class="form-input mt-1" placeholder="Barang rusak, salah ukuran, dll">
                    </div>
                    <div>
                        <x-input-label for="note" value="Catatan" />
                        <input id="note" name="note" type="text" maxlength="255" class="form-input mt-1" placeholder="Opsional">
                    </div>
                </div>

                <div class="rounded-lg bg-blue-50 border border-blue-100 px-4 py-3 text-xs text-blue-800">
                    Stok produk akan otomatis bertambah kembali sesuai jumlah yang diretur.
                </div>

                <div class="flex items-center justify-end gap-2">
                    <a href="{{ route('sales.show', $sale) }}" class="btn-secondary">Batal</a>
                    <button type="submit" class="btn-danger">Proses Retur</button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>