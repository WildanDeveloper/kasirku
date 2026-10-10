<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Pembelian {{ $purchase->invoice_number }}</h2>
            <p class="text-sm text-gray-500">
                {{ $purchase->supplier?->name ?? 'Tanpa supplier' }} &middot;
                {{ $purchase->purchase_date->format('d/m/Y') }} &middot;
                oleh {{ $purchase->user?->name ?? '-' }}
            </p>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 py-6 sm:px-6 lg:px-8 space-y-5">
        @if (session('success'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg bg-rose-50 border border-rose-100 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif

        @if ($purchase->status === 'cancelled')
            <div class="rounded-lg bg-gray-100 border border-gray-200 px-4 py-3 text-sm text-gray-600">Pembelian ini dibatalkan, stok sudah dikembalikan.</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
            <div class="lg:col-span-2 page-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="table-th">Produk</th>
                                <th class="table-th text-right">Qty</th>
                                <th class="table-th text-right">Harga Beli</th>
                                <th class="table-th text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($purchase->items as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="table-td text-gray-900">{{ $item->product->name }}</td>
                                    <td class="table-td text-right">{{ $item->quantity }}</td>
                                    <td class="table-td text-right">{{ number_format($item->cost_price, 0, ',', '.') }}</td>
                                    <td class="table-td text-right font-semibold">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-5">
                <div class="page-card p-5 space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>{{ number_format($purchase->subtotal, 0, ',', '.') }}</span></div>
                    @if ($purchase->discount > 0)
                        <div class="flex justify-between"><span class="text-gray-500">Diskon</span><span class="text-rose-600">-{{ number_format($purchase->discount, 0, ',', '.') }}</span></div>
                    @endif
                    <div class="flex justify-between font-bold text-base border-t border-gray-100 pt-2"><span>Total</span><span>{{ number_format($purchase->total, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Dibayar</span><span class="text-emerald-700">{{ number_format($purchase->paid_amount, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Sisa</span><span class="font-semibold {{ $purchase->outstanding() > 0 ? 'text-amber-700' : 'text-gray-400' }}">{{ number_format($purchase->outstanding(), 0, ',', '.') }}</span></div>
                    @if ($purchase->note)
                        <p class="pt-2 text-xs text-gray-500 border-t border-gray-100">{{ $purchase->note }}</p>
                    @endif
                </div>

                @if ($purchase->status === 'received' && $purchase->outstanding() > 0)
                    <div class="page-card p-5 space-y-3">
                        <h3 class="section-title">Bayar Supplier</h3>
                        <form method="POST" action="{{ route('purchases.pay', $purchase) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="form-label">Jumlah</label>
                                <input type="number" name="amount" min="1" max="{{ $purchase->outstanding() }}" value="{{ $purchase->outstanding() }}" class="form-input" required>
                            </div>
                            <div>
                                <label class="form-label">Metode</label>
                                <select name="payment_method" class="form-input">
                                    <option value="cash">Tunai</option>
                                    <option value="transfer">Transfer</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Catatan</label>
                                <input type="text" name="note" class="form-input">
                            </div>
                            <button class="btn-primary w-full">Bayar</button>
                        </form>
                    </div>
                @endif

                @if ($purchase->status === 'received' && $purchase->paid_amount === 0)
                    <form method="POST" action="{{ route('purchases.cancel', $purchase) }}"
                          onsubmit="return confirm('Batalkan pembelian ini dan kembalikan stok?')">
                        @csrf
                        <button class="btn-danger w-full">Batalkan Pembelian</button>
                    </form>
                @endif

                <div class="page-card p-5">
                    <h3 class="section-title mb-3">Riwayat Bayar</h3>
                    <div class="space-y-2 text-xs">
                        @forelse ($purchase->payments as $payment)
                            <div class="flex justify-between border-b border-gray-50 pb-2">
                                <span class="text-gray-600">{{ $payment->created_at->format('d/m/Y H:i') }} &middot; {{ $payment->payment_method === 'cash' ? 'Tunai' : 'Transfer' }}</span>
                                <span class="font-semibold text-emerald-700">{{ number_format($payment->amount, 0, ',', '.') }}</span>
                            </div>
                        @empty
                            <p class="text-gray-400">Belum ada pembayaran.</p>
                        @endforelse
                    </div>
                </div>

                <a href="{{ route('purchases.index') }}" class="btn-secondary w-full">Kembali</a>
            </div>
        </div>
    </div>
</x-app-layout>