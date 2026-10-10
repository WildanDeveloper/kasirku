<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Stok</h2>
            <p class="text-sm text-gray-500">Riwayat pergerakan dan pembaruan stok.</p>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <div class="lg:col-span-2 page-card overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h3 class="section-title">Riwayat Pergerakan Stok</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="table-th">Waktu</th>
                            <th class="table-th">Produk</th>
                            <th class="table-th">Jenis</th>
                            <th class="table-th text-right">Qty</th>
                            <th class="table-th text-right">Stok</th>
                            <th class="table-th">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($movements as $movement)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="table-td whitespace-nowrap text-gray-500">{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                                <td class="table-td font-semibold text-gray-900">{{ $movement->product->name }}</td>
                                <td class="table-td">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                        {{ $movement->type === 'in' ? 'bg-emerald-100 text-emerald-700' : ($movement->type === 'out' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                        {{ $movement->type === 'in' ? 'Masuk' : ($movement->type === 'out' ? 'Keluar' : 'Penyesuaian') }}
                                    </span>
                                </td>
                                <td class="table-td text-right font-semibold">{{ $movement->quantity }}</td>
                                <td class="table-td text-right text-gray-500 whitespace-nowrap">
                                    {{ $movement->stock_before }}
                                    <svg class="inline h-3 w-3 mx-0.5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                    <span class="font-semibold text-gray-800">{{ $movement->stock_after }}</span>
                                </td>
                                <td class="table-td text-gray-500">{{ $movement->note ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center">
                                    <p class="text-sm text-gray-500">Belum ada pergerakan stok.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($movements->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">
                    {{ $movements->links() }}
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="page-card p-5">
                <h3 class="section-title mb-4">Update Stok</h3>
                <form method="POST" action="{{ route('stocks.store') }}" class="space-y-3">
                    @csrf
                    <select name="product_id" required class="form-input">
                        <option value="">- Pilih produk -</option>
                        @foreach (\App\Models\Product::orderBy('name')->get() as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} (stok: {{ $product->stock }})</option>
                        @endforeach
                    </select>
                    <select name="type" class="form-input">
                        <option value="in">Stok masuk</option>
                        <option value="out">Stok keluar</option>
                        <option value="adjustment">Penyesuaian (set nilai)</option>
                    </select>
                    <input type="number" name="quantity" min="0" required placeholder="Jumlah" class="form-input">
                    <input type="text" name="note" placeholder="Catatan (opsional)" class="form-input">
                    <button class="w-full btn-primary">Simpan</button>
                </form>
            </div>

            <div class="page-card p-5">
                <h3 class="section-title mb-4">Stok Menipis</h3>
                <ul class="space-y-2">
                    @forelse ($lowStock as $product)
                        <li class="flex items-center justify-between rounded-lg px-3 py-2 {{ $product->stock === 0 ? 'bg-red-50' : 'bg-amber-50' }}">
                            <span class="text-sm font-medium {{ $product->stock === 0 ? 'text-red-700' : 'text-amber-700' }}">{{ $product->name }}</span>
                            <span class="text-xs font-bold {{ $product->stock === 0 ? 'text-red-600' : 'text-amber-600' }}">{{ $product->stock }} / min {{ $product->min_stock }}</span>
                        </li>
                    @empty
                        <li class="flex items-center gap-2 text-sm text-gray-500">
                            <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            Semua stok aman.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
