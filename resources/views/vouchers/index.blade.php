<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Voucher / Promo</h2>
            <p class="text-sm text-gray-500">Kode promo yang bisa dipakai kasir saat transaksi.</p>
        </div>
    </x-slot>

    <div x-data class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8 space-y-5">
        @if (session('success'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif
        @if (session('warning'))
            <div class="rounded-lg bg-amber-50 border border-amber-100 px-4 py-3 text-sm text-amber-700">{{ session('warning') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
            <div class="lg:col-span-2 page-card overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="section-title">Daftar Voucher</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="table-th">Kode</th>
                                <th class="table-th">Nama</th>
                                <th class="table-th">Potongan</th>
                                <th class="table-th text-right">Min. Belanja</th>
                                <th class="table-th text-right">Terpakai</th>
                                <th class="table-th">Masa Berlaku</th>
                                <th class="table-th">Status</th>
                                <th class="table-th text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($vouchers as $voucher)
                                <tr class="hover:bg-gray-50 align-top">
                                    <td class="table-td">
                                        <span class="font-mono font-bold text-gray-900">{{ $voucher->code }}</span>
                                        <button type="button" @click="navigator.clipboard?.writeText('{{ $voucher->code }}')"
                                                class="block text-[10px] text-emerald-600 hover:underline">salin</button>
                                    </td>
                                    <td class="table-td text-gray-600">{{ $voucher->name }}</td>
                                    <td class="table-td font-semibold {{ $voucher->type === 'percent' ? 'text-emerald-700' : 'text-blue-600' }}">{{ $voucher->label() }}</td>
                                    <td class="table-td text-right">{{ $voucher->min_purchase > 0 ? number_format($voucher->min_purchase, 0, ',', '.') : '—' }}</td>
                                    <td class="table-td text-right">
                                        {{ $voucher->used_count }}{{ $voucher->quota ? '/'.$voucher->quota : '' }}
                                    </td>
                                    <td class="table-td text-gray-500 text-xs whitespace-nowrap">
                                        {{ $voucher->starts_at?->format('d/m/Y') ?? '−' }} s/d {{ $voucher->expires_at?->format('d/m/Y') ?? '∞' }}
                                    </td>
                                    <td class="table-td">
                                        @php
                                            $status = $voucher->statusLabel();
                                            $statusClass = $status === 'Aktif' ? 'bg-emerald-100 text-emerald-700' : ($status === 'Kedaluwarsa' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600');
                                        @endphp
                                        <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $statusClass }}">{{ $status }}</span>
                                    </td>
                                    <td class="table-td text-right whitespace-nowrap">
                                        <button type="button" @click="document.getElementById('voucher-edit-{{ $loop->index }}').classList.toggle('hidden')"
                                                class="text-xs font-semibold text-emerald-600 hover:underline">Edit</button>
                                        <form method="POST" action="{{ route('vouchers.destroy', $voucher) }}" class="inline"
                                              @submit="if (! confirm('Hapus voucher {{ $voucher->code }}?')) $event.preventDefault()">
                                            @csrf @method('DELETE')
                                            <button class="text-xs font-semibold text-red-500 hover:text-red-700">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                <tr id="voucher-edit-{{ $loop->index }}" class="hidden bg-gray-50">
                                    <td colspan="8" class="px-4 py-4">
                                        <form method="POST" action="{{ route('vouchers.update', $voucher) }}" class="grid grid-cols-2 md:grid-cols-4 gap-3 items-end">
                                            @csrf @method('PUT')
                                            <div><x-input-label value="Kode" /><input name="code" value="{{ $voucher->code }}" class="form-input mt-1" required></div>
                                            <div><x-input-label value="Nama Promo" /><input name="name" value="{{ $voucher->name }}" class="form-input mt-1" required></div>
                                            <div>
                                                <x-input-label value="Jenis" />
                                                <select name="type" class="form-input mt-1">
                                                    <option value="percent" @selected($voucher->type === 'percent')>Persentase (%)</option>
                                                    <option value="fixed" @selected($voucher->type === 'fixed')>Nominal (Rp)</option>
                                                </select>
                                            </div>
                                            <div><x-input-label value="Nilai" /><input type="number" name="value" min="1" value="{{ $voucher->value }}" class="form-input mt-1" required></div>
                                            <div><x-input-label value="Min. Belanja" /><input type="number" name="min_purchase" min="0" value="{{ $voucher->min_purchase }}" class="form-input mt-1"></div>
                                            <div><x-input-label value="Kuota" /><input type="number" name="quota" min="1" value="{{ $voucher->quota }}" class="form-input mt-1" placeholder="∞"></div>
                                            <div><x-input-label value="Mulai" /><input type="date" name="starts_at" value="{{ $voucher->starts_at?->format('Y-m-d') }}" class="form-input mt-1"></div>
                                            <div><x-input-label value="Berakhir" /><input type="date" name="expires_at" value="{{ $voucher->expires_at?->format('Y-m-d') }}" class="form-input mt-1"></div>
                                            <div class="flex items-center gap-3">
                                                <label class="flex items-center gap-2 text-sm">
                                                    <input type="checkbox" name="is_active" value="1" @checked($voucher->is_active) class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                                    Aktif
                                                </label>
                                                <button class="btn-primary !py-1.5">Simpan</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="py-10 text-center text-sm text-gray-500">Belum ada voucher.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($vouchers->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">{{ $vouchers->links() }}</div>
                @endif
            </div>

            <div class="page-card p-5">
                <h3 class="section-title mb-4">Buat Voucher Baru</h3>
                <form method="POST" action="{{ route('vouchers.store') }}" class="space-y-3">
                    @csrf
                    <div><x-input-label for="code" value="Kode Voucher" /><input id="code" name="code" class="form-input mt-1" placeholder="HEMAT10" required></div>
                    <div><x-input-label for="name" value="Nama Promo" /><input id="name" name="name" class="form-input mt-1" placeholder="Diskon Ramadan" required></div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="type" value="Jenis" />
                            <select id="type" name="type" class="form-input mt-1">
                                <option value="percent">Persentase (%)</option>
                                <option value="fixed">Nominal (Rp)</option>
                            </select>
                        </div>
                        <div><x-input-label for="value" value="Nilai" /><input id="value" type="number" name="value" min="1" value="10" class="form-input mt-1" required></div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><x-input-label for="min_purchase" value="Min. Belanja" /><input id="min_purchase" type="number" name="min_purchase" min="0" value="0" class="form-input mt-1"></div>
                        <div><x-input-label for="quota" value="Kuota Pemakaian" /><input id="quota" type="number" name="quota" min="1" class="form-input mt-1" placeholder="∞"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><x-input-label for="starts_at" value="Mulai" /><input id="starts_at" type="date" name="starts_at" class="form-input mt-1"></div>
                        <div><x-input-label for="expires_at" value="Berakhir" /><input id="expires_at" type="date" name="expires_at" class="form-input mt-1"></div>
                    </div>
                    <div><x-input-label for="notes" value="Catatan" /><textarea id="notes" name="notes" rows="2" class="form-input mt-1"></textarea></div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        Aktif sekarang
                    </label>
                    <button class="w-full btn-primary">Simpan Voucher</button>
                </form>
            </div>
        </div>
    </div>

</x-app-layout>