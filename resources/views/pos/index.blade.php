<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900 leading-tight">Kasir</h2>
                <p class="text-sm text-gray-500">Scan barcode atau klik produk untuk masuk keranjang.</p>
            </div>
            <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                Kasir: {{ auth()->user()->name }}
            </span>
        </div>
    </x-slot>

    @php
    // JSON aman ditaruh di atribut HTML (tanpa kutip mentah).
    $attrJson = fn ($value) => json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8"
         x-data="pos"
         data-products="{{ $attrJson($products) }}"
         data-customers="{{ $attrJson($customers) }}"
         data-custom-prices="{{ $attrJson($customPrices) }}"
         data-vouchers="{{ $attrJson($vouchers) }}"
         data-tax-percent="{{ $taxPercent }}"
         data-open-shift="{{ $openShift ? 1 : 0 }}"
         data-held-sales="{{ $attrJson($heldSales) }}"
         data-checkout-url="{{ route('pos.checkout') }}"
         data-hold-url="{{ route('pos.hold') }}"
         data-hold-destroy-url="{{ route('pos.hold.destroy', ':id') }}"
         data-voucher-check-url="{{ route('vouchers.check', ':code') }}">
        @unless ($openShift)
            <div class="mb-4 flex flex-wrap items-center gap-3 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3">
                <svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <span class="text-sm font-medium text-amber-800 flex-1">Buka shift kasir dulu sebelum bertransaksi.</span>
                <a href="{{ route('shifts.index') }}" class="btn-primary !py-1.5">Buka Shift</a>
            </div>
        @endunless

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <!-- Product list -->
            <div class="lg:col-span-2 page-card p-5">
                <div class="relative mb-4">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                    <input type="text" x-model="search" x-ref="search" placeholder="Cari nama / ketik barcode lalu Enter..."
                           @keydown.enter.prevent="addFromSearch()"
                           class="w-full rounded-lg border-gray-300 pl-11 pr-24 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500" autofocus>
                    <button type="button" @click="startScanner()"
                            class="absolute right-1.5 top-1/2 -translate-y-1/2 inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md bg-emerald-50 text-emerald-700 text-xs font-semibold hover:bg-emerald-100 transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5V5.25A1.5 1.5 0 0 1 5.25 3.75H7.5M2.25 12h1.5m-1.5 4.5h1.5m1.5-12h1.5m6 0H15m3.75 3.75H21m-3.75 6H21m-9.75 3.75H9.75M6 18.75h.008v.008H6v-.008Zm0-4.5h.008v.008H6v-.008Zm3 4.5h.008v.008H9v-.008Zm0-4.5h.008v.008H9v-.008Zm3 4.5h.008v.008H12v-.008Zm0-4.5h.008v.008H12v-.008Zm3 4.5h.008v.008H15v-.008Zm0-4.5h.008v.008H15v-.008ZM16.5 3.75h2.25A1.5 1.5 0 0 1 20.25 5.25v2.25m0 9v2.25a1.5 1.5 0 0 1-1.5 1.5h-2.25M3.75 18.75H5.25A1.5 1.5 0 0 0 6.75 17.25V15" />
                        </svg>
                        Scan
                    </button>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 max-h-[30rem] overflow-y-auto pr-1">
                    <template x-for="product in filtered" :key="product.id">
                        <button type="button" @click="add(product)" :disabled="product.stock === 0"
                                class="group relative text-left border rounded-xl p-4 transition
                                       disabled:opacity-50 disabled:cursor-not-allowed
                                       enabled:hover:border-emerald-500 enabled:hover:bg-emerald-50 enabled:hover:shadow-sm">
                            <span x-show="product.stock === 0"
                                  class="absolute top-2 right-2 px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-600 uppercase">Habis</span>
                            <span x-show="product.stock > 0 && product.stock <= 5"
                                  class="absolute top-2 right-2 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 uppercase">Menipis</span>
                            <div class="font-semibold text-sm text-gray-900" x-text="product.name"></div>
                            <div class="mt-1 text-sm font-bold text-emerald-600" x-text="format(priceOf(product))"></div>
                            <div class="mt-1 text-xs text-gray-500" x-text="'Stok: ' + product.stock + ' ' + product.unit"></div>
                        </button>
                    </template>
                    <div x-show="filtered.length === 0" class="col-span-full py-10 text-center">
                        <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-500">Produk tidak ditemukan.</p>
                    </div>
                </div>
            </div>

            <!-- Cart -->
            <div class="page-card p-5 flex flex-col">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="section-title">Keranjang</h3>
                    <div class="flex items-center gap-3">
                        <button type="button" x-show="cart.length > 0" @click="holdOrder()"
                                class="text-xs font-semibold text-amber-600 hover:text-amber-700">Tahan</button>
                        <button type="button" x-show="cart.length > 0" @click="clearCart()"
                                class="text-xs font-semibold text-red-500 hover:text-red-700">Kosongkan</button>
                    </div>
                </div>

                <!-- Transaksi tertahan -->
                <div x-show="heldSales.length > 0" x-cloak class="mb-3 rounded-lg bg-amber-50 border border-amber-100 p-3">
                    <div class="text-xs font-bold text-amber-800 uppercase tracking-wide mb-2"
                         x-text="'Ditahan (' + heldSales.length + ')'"></div>
                    <template x-for="held in heldSales" :key="held.id">
                        <div class="flex items-center gap-2 py-1">
                            <span class="flex-1 text-xs text-amber-900 truncate"
                                  x-text="(held.note || 'Transaksi') + ' — ' + held.items.length + ' item'"></span>
                            <button type="button" @click="resumeHeld(held)"
                                    class="text-xs font-bold text-emerald-700 hover:text-emerald-500">Lanjutkan</button>
                            <button type="button" @click="deleteHeld(held)"
                                    class="text-xs font-semibold text-red-500 hover:text-red-700">&times;</button>
                        </div>
                    </template>
                </div>

                <div class="space-y-3 flex-1 overflow-y-auto max-h-72 -mx-1 px-1">
                    <template x-for="(item, index) in cart" :key="item.id">
                        <div class="flex items-center gap-2 rounded-lg bg-gray-50 border border-gray-100 p-2.5">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-gray-900 truncate" x-text="item.name"></div>
                                <div class="text-xs text-gray-500" x-text="format(priceOf(item)) + ' x ' + item.quantity + ' = ' + format(priceOf(item) * item.quantity)"></div>
                            </div>
                            <div class="flex items-center rounded-lg border border-gray-200 bg-white overflow-hidden">
                                <button type="button" @click="dec(item)" class="px-2 py-1 text-gray-500 hover:bg-gray-100 hover:text-gray-800">&minus;</button>
                                <input type="number" min="1" :max="item.stock" x-model.number="item.quantity" @change="clamp(item)"
                                       class="w-12 border-0 text-center text-sm focus:ring-0 p-0">
                                <button type="button" @click="inc(item)" class="px-2 py-1 text-gray-500 hover:bg-gray-100 hover:text-gray-800">&plus;</button>
                            </div>
                            <button type="button" @click="removeItem(index)" class="text-gray-300 hover:text-red-500 transition">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </template>
                    <div x-show="cart.length === 0" class="py-8 text-center">
                        <svg class="mx-auto h-10 w-10 text-gray-200" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-500">Keranjang masih kosong.</p>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-dashed border-gray-200 space-y-3">
                    <div class="flex items-end justify-between">
                        <span class="text-sm font-medium text-gray-500">Subtotal</span>
                        <span class="text-sm font-bold text-gray-900" x-text="format(subtotal)"></span>
                    </div>

                    <!-- Voucher -->
                    <div class="flex gap-2">
                        <input type="text" x-model="voucherCode" @keydown.enter.prevent="applyVoucher()"
                               placeholder="Kode voucher" class="form-input uppercase"
                               :disabled="voucherDiscount > 0">
                        <button type="button" @click="applyVoucher()" x-show="voucherDiscount === 0"
                                class="btn-secondary !px-3 whitespace-nowrap">Pakai</button>
                        <button type="button" @click="removeVoucher()" x-show="voucherDiscount > 0"
                                class="btn-secondary !px-3 whitespace-nowrap text-red-600">Batal</button>
                    </div>
                    <p x-show="voucherMessage" class="text-xs" :class="voucherError ? 'text-red-600' : 'text-emerald-600'" x-text="voucherMessage"></p>

                    <div class="flex justify-between text-sm" x-show="discount > 0">
                        <span class="text-gray-500">Diskon</span>
                        <span class="font-semibold text-amber-600">- <span x-text="format(discount)"></span></span>
                    </div>
                    <div class="flex justify-between text-sm" x-show="voucherDiscount > 0">
                        <span class="text-gray-500">Voucher <span x-text="voucherName"></span></span>
                        <span class="font-semibold text-emerald-600">- <span x-text="format(voucherDiscount)"></span></span>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" min="0" x-model.number="discountInput" placeholder="Diskon (Rp)"
                               class="form-input">
                        <select x-model="customerId" class="form-input">
                            <option value="">Tanpa pelanggan</option>
                            <template x-for="c in customers" :key="c.id">
                                <option :value="c.id" x-text="c.name"></option>
                            </template>
                        </select>
                    </div>

                    <label x-show="taxPercent > 0" class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" x-model="applyTax" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        Pajak <span class="font-semibold" x-text="taxPercent + '%'"></span>
                        <span class="ml-auto text-gray-500" x-text="'+' + format(taxAmount)"></span>
                    </label>

                    <div class="flex items-end justify-between">
                        <span class="text-sm font-medium text-gray-500">Total</span>
                        <span class="text-2xl font-extrabold text-gray-900 tracking-tight" x-text="format(total)"></span>
                    </div>

                    <div class="grid grid-cols-3 gap-1.5 rounded-lg bg-gray-100 p-1">
                        <template x-for="m in [
                            { value: 'cash', label: 'Tunai' },
                            { value: 'qris', label: 'QRIS' },
                            { value: 'debit', label: 'Debit' },
                        ]" :key="m.value">
                            <button type="button" @click="paymentMethod = m.value"
                                    class="py-1.5 text-xs font-semibold rounded-md transition"
                                    :class="paymentMethod === m.value ? 'bg-white text-emerald-700 shadow-sm' : 'text-gray-500 hover:text-gray-800'"
                                    x-text="m.label"></button>
                        </template>
                    </div>

                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2.5 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 transition">
                        <input type="checkbox" x-model="isCredit" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="flex-1">Utang (piutang pelanggan)</span>
                    </label>
                    <p x-show="isCredit && !customerId" class="text-xs text-red-600">Pilih pelanggan dulu untuk transaksi utang.</p>

                    <div x-show="paymentMethod === 'cash' && !isCredit" class="space-y-2">
                        <div class="grid grid-cols-4 gap-1.5">
                            <template x-for="amount in [total, 20000, 50000, 100000]" :key="amount">
                                <button type="button" @click="paidAmount = amount"
                                        class="py-1.5 rounded-md border border-gray-200 text-xs font-semibold text-gray-600 hover:border-emerald-400 hover:text-emerald-600 transition">
                                    <span x-text="amount === total ? 'Pas' : format(amount)"></span>
                                </button>
                            </template>
                        </div>
                        <input type="number" min="0" x-model.number="paidAmount" :placeholder="isCredit ? 'Uang muka (DP)' : 'Uang diterima'"
                               class="form-input">
                        <div class="flex justify-between text-sm" x-show="!isCredit">
                            <span class="text-gray-500">Kembalian</span>
                            <span class="font-bold" :class="paidAmount >= total ? 'text-emerald-600' : 'text-gray-400'"
                                  x-text="changeText()"></span>
                        </div>
                        <div class="flex justify-between text-sm" x-show="isCredit">
                            <span class="text-gray-500">Sisa utang</span>
                            <span class="font-bold text-amber-600" x-text="remainingText()"></span>
                        </div>
                    </div>

                    <button type="button" @click="checkout"
                            :disabled="cart.length === 0 || loading || (isCredit && !customerId) || !openShift"
                            class="w-full py-3 btn-primary text-base disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isCredit || (isCredit && customerId)">Bayar Sekarang</span>
                        <span x-show="loading" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Memproses...
                        </span>
                    </button>

                    <div x-show="message" x-transition class="rounded-lg p-3 text-sm font-medium"
                         :class="success ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'">
                        <p x-text="message"></p>
                        <a x-show="receiptUrl" :href="receiptUrl" class="mt-1 inline-flex items-center gap-1 font-bold underline">
                            Lihat struk
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal scan barcode -->
    <div x-show="scannerOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4"
         @keydown.escape.window="stopScanner()">
        <div class="w-full max-w-md bg-white rounded-xl shadow-xl overflow-hidden" @click.outside="stopScanner()">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                <h3 class="text-sm font-bold text-gray-900">Scan Barcode</h3>
                <button type="button" @click="stopScanner()" class="text-gray-400 hover:text-gray-700">&times;</button>
            </div>
            <div id="qr-reader" class="w-full bg-black"></div>
            <div class="px-4 py-3 text-xs text-gray-500">
                Arahkan kamera ke barcode produk. Bisa juga ketik barcode di kotak pencarian lalu tekan Enter.
            </div>
        </div>
    </div>

</x-app-layout>
