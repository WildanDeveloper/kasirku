<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Tambah Pembelian</h2>
            <p class="text-sm text-gray-500">Barang masuk otomatis menambah stok dan memperbarui harga beli.</p>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('purchases.store') }}"
          x-data="purchaseForm(@js($products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'barcode' => $p->barcode, 'cost' => $p->cost_price])))"
          class="max-w-6xl mx-auto px-4 py-6 sm:px-6 lg:px-8 space-y-5">
        @csrf
        @if ($errors->any())
            <div class="rounded-lg bg-rose-50 border border-rose-100 px-4 py-3 text-sm text-rose-700">
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="page-card p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="form-label">Nomor PO / Nota</label>
                <input type="text" name="invoice_number" class="form-input font-mono" placeholder="Otomatis: {{ \App\Models\Purchase::nextInvoiceNumber() }}">
            </div>
            <div>
                <label class="form-label">Supplier</label>
                <select name="supplier_id" class="form-input">
                    <option value="">— Tanpa supplier —</option>
                    @foreach ($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Tanggal</label>
                <input type="date" name="purchase_date" value="{{ old('purchase_date', $purchase->purchase_date?->toDateString() ?? today()->toDateString()) }}" class="form-input" required>
            </div>
        </div>

        <div class="page-card overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between gap-3">
                <h3 class="section-title">Barang Masuk</h3>
                <button type="button" @click="addRow()" class="btn-secondary !py-1.5">+ Tambah Baris</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="table-th">Produk</th>
                            <th class="table-th w-28 text-right">Qty</th>
                            <th class="table-th w-40 text-right">Harga Beli</th>
                            <th class="table-th w-44 text-right">Subtotal</th>
                            <th class="table-th w-12"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(row, i) in rows" :key="i">
                            <tr>
                                <td class="table-td">
                                    <select name="items[__i__][product_id]" class="form-input" x-model.number="row.product_id" required>
                                        <option value="">— pilih produk —</option>
                                        <template x-for="p in products" :key="p.id">
                                            <option :value="p.id" x-text="p.name + (p.barcode ? ' (' + p.barcode + ')' : '')"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="table-td">
                                    <input type="number" min="1" name="items[__i__][quantity]" class="form-input text-right" x-model.number="row.quantity" required>
                                </td>
                                <td class="table-td">
                                    <input type="number" min="0" name="items[__i__][cost_price]" class="form-input text-right"
                                           x-model.number="row.cost_price" @input="row.cost_price = Math.max(0, +row.cost_price || 0)" required>
                                </td>
                                <td class="table-td text-right font-semibold" x-text="format(row.quantity * row.cost_price)"></td>
                                <td class="table-td text-center">
                                    <button type="button" @click="removeRow(i)" class="text-rose-600 hover:text-rose-800" x-show="rows.length > 1">&times;</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="p-5 border-t border-gray-100 space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Subtotal</span>
                    <span class="font-semibold" x-text="format(subtotal)"></span>
                </div>
                <div class="flex justify-between items-center text-sm gap-4">
                    <span class="text-gray-500">Diskon</span>
                    <input type="number" min="0" name="discount" class="form-input !py-1.5 w-40 text-right" x-model.number="discount">
                </div>
                <div class="flex justify-between items-center text-sm gap-4">
                    <span class="text-gray-500">Dibayar</span>
                    <input type="number" min="0" name="paid_amount" class="form-input !py-1.5 w-40 text-right" x-model.number="paid">
                </div>
                <div class="flex justify-between text-base font-bold border-t border-gray-100 pt-3">
                    <span>Total</span>
                    <span x-text="format(total)"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Sisa utang supplier</span>
                    <span class="font-semibold text-amber-700" x-text="format(outstanding)"></span>
                </div>
            </div>
        </div>

        <div class="page-card p-5">
            <label class="form-label">Catatan</label>
            <textarea name="note" rows="2" class="form-input" placeholder="Opsional">{{ old('note') }}</textarea>
        </div>

        <div class="flex gap-3">
            <button class="btn-primary">Simpan Pembelian</button>
            <a href="{{ route('purchases.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
</x-app-layout>