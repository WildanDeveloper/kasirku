<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Hitung Fisik &mdash; {{ $opname->name }}</h2>
            <p class="text-sm text-gray-500">Masukkan jumlah fisik, selisih dihitung otomatis.</p>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto px-4 py-6 sm:px-6 lg:px-8 space-y-5">
        @if (session('success'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif
        @if (session('warning'))
            <div class="rounded-lg bg-amber-50 border border-amber-100 px-4 py-3 text-sm text-amber-700">{{ session('warning') }}</div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-stat-card label="Produk Dihitung" :value="$opname->items->whereNotNull('physical_qty')->count().' / '.$opname->items->count()" />
            <x-stat-card label="Total Selisih" :value="($opname->totalDifference() > 0 ? '+' : '').$opname->totalDifference().' item'" tone="blue" />
            <x-stat-card label="Nilai Selisih" :value="'Rp '.number_format($opname->difference_value, 0, ',', '.')" :tone="$opname->difference_value < 0 ? 'red' : 'emerald'" />
        </div>

        @if ($opname->isFinished())
            <div class="page-card p-5 flex items-center justify-between gap-4">
                <div class="text-sm text-gray-600">
                    Selesai {{ $opname->finished_at?->format('d/m/Y H:i') }} oleh {{ $opname->user?->name ?? '-' }}.
                    @if ($opname->note)<span class="block text-gray-500">{{ $opname->note }}</span>@endif
                </div>
                <a href="{{ route('opnames.index') }}" class="btn-secondary">← Kembali</a>
            </div>
        @else
            <form method="POST" action="{{ route('opnames.finish', $opname) }}" class="page-card p-5 flex items-end gap-3"
                  onsubmit="return confirm('Selesaikan opname? Selisih akan langsung adjusts stok.')">
                @csrf
                <div class="flex-1">
                    <label class="form-label">Catatan Hasil Opname</label>
                    <input type="text" name="note" value="{{ $opname->note }}" class="form-input" placeholder="Contoh: opname akhir bulan, gudang belakang">
                </div>
                <button class="btn-primary">Selesaikan Opname</button>
            </form>

            <form method="POST" action="{{ route('opnames.items.update', $opname) }}">
                @csrf
                <div class="page-card overflow-hidden">
                    <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-3">
                        <h3 class="section-title">Hasil Hitung</h3>
                        <button class="btn-secondary !py-1.5">Simpan Hitungan</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="table-th">Produk</th>
                                    <th class="table-th w-28 text-right">Stok Sistem</th>
                                    <th class="table-th w-40 text-right">Stok Fisik</th>
                                    <th class="table-th w-24 text-right">Selisih</th>
                                    <th class="table-th w-36 text-right">Nilai</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($opname->items as $item)
                                    <tr class="hover:bg-gray-50">
                                        <td class="table-td">
                                            <span class="font-medium text-gray-900">{{ $item->product->name }}</span>
                                            @if ($item->product->barcode)
                                                <span class="block font-mono text-[10px] text-gray-400">{{ $item->product->barcode }}</span>
                                            @endif
                                        </td>
                                        <td class="table-td text-right text-gray-600">{{ $item->system_qty }}</td>
                                        <td class="table-td">
                                            <input type="number" min="0" name="items[{{ $item->id }}]"
                                                   value="{{ $item->physical_qty }}"
                                                   class="form-input !py-1 text-right"
                                                   placeholder="belum">
                                        </td>
                                        <td class="table-td text-right font-semibold {{ $item->difference < 0 ? 'text-rose-700' : ($item->difference > 0 ? 'text-emerald-700' : 'text-gray-400') }}">
                                            {{ $item->physical_qty === null ? '-' : ($item->difference > 0 ? '+' : '').$item->difference }}
                                        </td>
                                        <td class="table-td text-right text-gray-600">
                                            {{ $item->physical_qty === null ? '-' : number_format($item->difference * $item->product->cost_price, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>
        @endif

        @unless ($opname->isFinished())
            <a href="{{ route('opnames.index') }}" class="btn-secondary">← Kembali</a>
        @endunless
    </div>
</x-app-layout>