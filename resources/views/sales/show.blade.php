<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Struk {{ $sale->invoice_number }}</h2>
            <p class="text-sm text-gray-500">{{ $sale->created_at->format('d/m/Y H:i') }}</p>
        </div>
    </x-slot>

    <div class="max-w-md mx-auto px-4 py-6 sm:px-6">
        <div id="receipt" class="page-card p-6 font-mono text-sm">
            <div class="text-center mb-4">
                <div class="font-bold text-lg">{{ \App\Models\Setting::get('store_name') }}</div>
                @if (\App\Models\Setting::get('store_address'))
                    <div class="text-gray-500 text-xs">{{ \App\Models\Setting::get('store_address') }}</div>
                @endif
                @if (\App\Models\Setting::get('store_phone'))
                    <div class="text-gray-500 text-xs">Telp: {{ \App\Models\Setting::get('store_phone') }}</div>
                @endif
                <div class="text-gray-500 text-xs mt-1">{{ $sale->created_at->format('d/m/Y H:i') }}</div>
                <div class="text-gray-500 text-xs">{{ $sale->invoice_number }}</div>
                @if ($sale->customer)
                    <div class="text-gray-700 text-xs font-semibold mt-1">Pelanggan: {{ $sale->customer->name }}</div>
                @endif
            </div>

            <div class="border-t border-dashed border-gray-300 my-3"></div>

            @foreach ($sale->items as $item)
                <div class="flex justify-between mb-1.5">
                    <span class="mr-3">{{ $item->product->name }}<br>
                        <span class="text-gray-500">{{ $item->quantity }} x Rp {{ number_format($item->price, 0, ',', '.') }}</span>
                    </span>
                    <span class="font-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                </div>
            @endforeach

            <div class="border-t border-dashed border-gray-300 my-3"></div>

            <div class="flex justify-between"><span>Subtotal</span><span>Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</span></div>
            @if ($sale->discount > 0)
                <div class="flex justify-between"><span>Diskon</span><span>- Rp {{ number_format($sale->discount, 0, ',', '.') }}</span></div>
            @endif
            @if ($sale->voucher_discount > 0)
                <div class="flex justify-between"><span>Voucher {{ $sale->voucher?->code }}</span><span>- Rp {{ number_format($sale->voucher_discount, 0, ',', '.') }}</span></div>
            @endif
            @if ($sale->tax > 0)
                <div class="flex justify-between"><span>Pajak</span><span>Rp {{ number_format($sale->tax, 0, ',', '.') }}</span></div>
            @endif
            <div class="flex justify-between font-bold"><span>TOTAL</span><span>Rp {{ number_format($sale->total, 0, ',', '.') }}</span></div>
            @if ($sale->is_credit)
                <div class="flex justify-between mt-1"><span>Dibayar</span><span>Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</span></div>
                <div class="flex justify-between font-bold {{ $sale->settled_at ? 'text-emerald-600' : 'text-amber-600' }}">
                    <span>{{ $sale->settled_at ? 'LUNAS' : 'SISA UTANG' }}</span>
                    <span>Rp {{ number_format($sale->remainingAmount(), 0, ',', '.') }}</span>
                </div>
                @foreach ($sale->payments as $payment)
                    <div class="flex justify-between text-gray-400 text-xs"><span>Cicilan {{ $payment->created_at->format('d/m H:i') }}</span><span>Rp {{ number_format($payment->amount, 0, ',', '.') }}</span></div>
                @endforeach
            @else
                <div class="flex justify-between mt-1"><span>Bayar ({{ strtoupper($sale->payment_method) }})</span><span>Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span>Kembali</span><span>Rp {{ number_format($sale->change_amount, 0, ',', '.') }}</span></div>
            @endif

            <div class="border-t border-dashed border-gray-300 my-3"></div>

            <div class="text-center text-gray-500">
                Kasir: {{ $sale->user?->name ?? '-' }}<br>
                {{ \App\Models\Setting::get('receipt_footer') }}
            </div>
        </div>

        @foreach ($sale->returns as $return)
            <div class="mt-3 page-card p-4 text-sm print:hidden border-red-100">
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-red-700">Retur {{ $return->created_at->format('d/m/Y H:i') }}</span>
                    <span class="font-bold text-red-600">Rp {{ number_format($return->total, 0, ',', '.') }}</span>
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $return->reason ?? 'Tanpa alasan' }} ·
                    {{ $return->refund_method === 'cash' ? 'Kembalikan uang' : 'Barang saja (tanpa uang)' }}
                    @if ($return->debt_reduction > 0) · mengurangi utang Rp {{ number_format($return->debt_reduction, 0, ',', '.') }} @endif
                    · oleh {{ $return->user?->name }}
                </div>
                <div class="text-xs text-gray-600 mt-1">
                    @foreach ($return->items as $item)
                        <span class="inline-block mr-2">{{ $item->product->name }} {{ $item->quantity }}×</span>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="mt-4 space-y-2 print:hidden">
            @if ($sale->returnableItems()->isNotEmpty())
                <a href="{{ route('sales.return.create', $sale) }}" class="w-full btn-secondary">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                    </svg>
                    Retur Barang
                </a>
            @endif
            @if ($sale->is_credit && ! $sale->settled_at)
                <form method="POST" action="{{ route('sales.pay', $sale) }}" class="page-card p-4 flex flex-wrap gap-2 items-end">
                    @csrf
                    <div class="flex-1 min-w-32">
                        <x-input-label for="amount" value="Bayar utang (Rp)" />
                        <x-text-input id="amount" name="amount" type="number" min="1" max="{{ $sale->remainingAmount() }}" value="{{ $sale->remainingAmount() }}" class="mt-1 form-input" />
                    </div>
                    <select name="payment_method" class="form-input w-28">
                        <option value="cash">Tunai</option>
                        <option value="qris">QRIS</option>
                        <option value="debit">Debit</option>
                    </select>
                    <button class="btn-primary">Bayar</button>
                </form>
            @endif
            <div class="flex justify-center gap-2">
                <button onclick="window.print()" class="btn-primary">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                    </svg>
                    Cetak
                </button>
                <a href="{{ route('sales.index') }}" class="btn-secondary">Kembali</a>
            </div>
        </div>
    </div>
</x-app-layout>
