<x-app-layout>
<div x-data="bulkProducts({{ json_encode($products->pluck('id'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }})">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900 leading-tight">Produk</h2>
                <p class="text-sm text-gray-500">Kelola daftar produk dan harganya.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('products.import.form') }}" class="btn-secondary">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                    </svg>
                    Import
                </a>
                <a href="{{ route('products.create') }}" class="btn-primary">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Produk
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 flex items-center gap-2 rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                {{ session('success') }}
            </div>
        @endif
        @if (session('warning'))
            <div class="mb-4 flex items-center gap-2 rounded-lg bg-amber-50 border border-amber-100 px-4 py-3 text-sm text-amber-700">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                {{ session('warning') }}
            </div>
        @endif

        <div class="page-card overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row gap-2">
                <form method="GET" class="flex flex-1 gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / barcode..."
                           class="form-input flex-1">
                    <select name="category_id" class="form-input sm:w-48">
                        <option value="">Semua kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn-secondary">Cari</button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="table-th w-10">
                                <input type="checkbox" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                       @change="toggleAll($event.target.checked)"
                                       :checked="selected.length === @json($products->count()) && @json($products->count()) > 0">
                            </th>
                            <th class="table-th">Produk</th>
                            <th class="table-th">Kategori</th>
                            <th class="table-th text-right">Harga Beli</th>
                            <th class="table-th text-right">Harga Jual</th>
                            <th class="table-th text-right">Stok</th>
                            <th class="table-th"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($products as $product)
                            <tr class="hover:bg-gray-50 transition {{ $product->isLowStock() ? 'bg-red-50/60' : '' }}">
                                <td class="table-td">
                                    <input type="checkbox" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                           :value="{{ $product->id }}" x-model.number="selected">
                                </td>
                                <td class="table-td">
                                    <a href="{{ route('products.show', $product) }}" class="font-semibold text-gray-900 hover:text-emerald-600 hover:underline">{{ $product->name }}</a>
                                    <div class="text-xs text-gray-400 font-mono">{{ $product->barcode ?? '—' }}</div>
                                </td>
                                <td class="table-td">
                                    @if ($product->category)
                                        <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs font-medium">{{ $product->category->name }}</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="table-td text-right text-gray-500">Rp {{ number_format($product->cost_price, 0, ',', '.') }}</td>
                                <td class="table-td text-right font-semibold">
                                    Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                                    @if ($product->price_wholesale)
                                        <span class="block text-[10px] text-blue-500">Grosir: {{ number_format($product->price_wholesale, 0, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="table-td text-right">
                                    <span class="inline-flex items-center gap-1 font-semibold {{ $product->isLowStock() ? 'text-red-600' : 'text-gray-700' }}">
                                        {{ $product->stock }} {{ $product->unit }}
                                    </span>
                                    @if ($product->isLowStock())
                                        <span class="block text-[10px] uppercase font-bold text-red-400">Stok menipis</span>
                                    @endif
                                </td>
                                <td class="table-td text-right whitespace-nowrap">
                                    <a href="{{ route('products.edit', $product) }}"
                                       class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-600 hover:text-emerald-500">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487 18.549 2.8a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                        </svg>
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('products.destroy', $product) }}" class="inline"
                                          @submit="if (! confirm('Hapus produk ini?')) $event.preventDefault()">
                                        @csrf
                                        @method('DELETE')
                                        <button class="inline-flex items-center gap-1 text-sm font-semibold text-red-500 hover:text-red-700 ml-3">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center">
                                    <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">Belum ada produk.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Bulk action bar -->
            <form method="POST" action="{{ route('products.bulk') }}"
                  x-show="selected.length > 0" x-cloak x-transition
                  class="sticky bottom-0 bg-slate-900 border-t border-slate-700 px-4 py-3">
                @csrf
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-semibold text-white px-1" x-text="selected.length + ' dipilih'"></span>

                    <select x-model="action" class="form-input !w-auto bg-white">
                        <option value="category">Set kategori</option>
                        <option value="price_percent">Ubah harga jual (%)</option>
                        <option value="price_set">Set harga jual</option>
                        <option value="restock">Tambah/kurangi stok</option>
                        <option value="delete">Hapus</option>
                    </select>

                    <template x-if="action === 'category'">
                        <select name="category_id" class="form-input !w-auto bg-white">
                            <option value="">- Tanpa kategori -</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </template>

                    <template x-if="action === 'price_percent'">
                        <input type="number" name="percent" min="-99" max="1000" placeholder="% (mis. 10 atau -5)" required class="form-input !w-48 bg-white">
                    </template>

                    <template x-if="action === 'price_set'">
                        <input type="number" name="price" min="0" placeholder="Harga jual baru (Rp)" required class="form-input !w-48 bg-white">
                    </template>

                    <template x-if="action === 'restock'">
                        <input type="number" name="stock_delta" min="-100000" max="100000" placeholder="+20 atau -5" required class="form-input !w-48 bg-white">
                    </template>

                    <button type="submit" x-show="action !== 'delete'"
                            class="btn-primary !bg-white !text-slate-900 hover:!bg-gray-100">Terapkan</button>
                    <button type="submit" x-show="action === 'delete'"
                            class="btn-danger"
                            @submit="askBulkDelete($event)">Hapus Terpilih</button>

                    <button type="submit" form="label-form"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-white/10 text-white text-sm font-semibold rounded-lg hover:bg-white/20 transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                        </svg>
                        Cetak Label
                    </button>

                    <button type="button" @click="selected = []" class="ml-auto text-xs font-semibold text-slate-400 hover:text-white">Batal</button>
                </div>
            </form>

            <!-- Form terpisah untuk cetak label (dipakai lewat atribut form=) -->
            <form id="label-form" method="POST" action="{{ route('products.labels') }}" class="hidden">
                @csrf
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
                <input type="hidden" name="mode" value="retail">
            </form>

            @if ($products->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
        </div>
    </div>
</x-app-layout>