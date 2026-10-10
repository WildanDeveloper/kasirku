<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Shift Kas</h2>
            <p class="text-sm text-gray-500">Buka/tutup shift dan setoran kas.</p>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif
        @if (session('warning'))
            <div class="mb-4 rounded-lg bg-amber-50 border border-amber-100 px-4 py-3 text-sm text-amber-700">{{ session('warning') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div class="lg:col-span-2 page-card overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="section-title">Riwayat Shift</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="table-th">Kasir</th>
                                <th class="table-th">Dibuka</th>
                                <th class="table-th">Ditutup</th>
                                <th class="table-th text-right">Modal</th>
                                <th class="table-th text-right">Harapan</th>
                                <th class="table-th text-right">Hitungan</th>
                                <th class="table-th text-right">Selisih</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($shifts as $shift)
                                <tr class="hover:bg-gray-50 transition {{ $shift->closed_at ? '' : 'bg-emerald-50/50' }}">
                                    <td class="table-td font-semibold text-gray-900">{{ $shift->user->name }}</td>
                                    <td class="table-td whitespace-nowrap text-gray-500">{{ $shift->opened_at->format('d/m H:i') }}</td>
                                    <td class="table-td whitespace-nowrap text-gray-500">
                                        @if ($shift->closed_at)
                                            {{ $shift->closed_at->format('d/m H:i') }}
                                        @else
                                            <span class="text-emerald-600 font-semibold">Aktif</span>
                                        @endif
                                    </td>
                                    <td class="table-td text-right">{{ number_format($shift->opening_balance, 0, ',', '.') }}</td>
                                    <td class="table-td text-right">{{ $shift->expected_balance !== null ? number_format($shift->expected_balance, 0, ',', '.') : '—' }}</td>
                                    <td class="table-td text-right">{{ $shift->closing_balance !== null ? number_format($shift->closing_balance, 0, ',', '.') : '—' }}</td>
                                    <td class="table-td text-right font-semibold">
                                        @if ($shift->closing_balance !== null)
                                            @php $diff = $shift->closing_balance - $shift->expected_balance; @endphp
                                            <span class="{{ $diff === 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                                {{ ($diff >= 0 ? '+' : '') . number_format($diff, 0, ',', '.') }}
                                            </span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-10 text-center text-sm text-gray-500">Belum ada shift.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($shifts->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">{{ $shifts->links() }}</div>
                @endif
            </div>

            <div class="page-card p-5">
                @if ($openShift)
                    <h3 class="section-title mb-1">Shift Aktif</h3>
                    <p class="text-xs text-gray-500 mb-4">Dibuka {{ $openShift->opened_at->format('d/m/Y H:i') }}</p>
                    <div class="rounded-lg bg-gray-50 p-3 text-sm space-y-1 mb-4">
                        <div class="flex justify-between"><span class="text-gray-500">Modal awal</span><span class="font-semibold">Rp {{ number_format($openShift->opening_balance, 0, ',', '.') }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Kas seharusnya sekarang</span><span class="font-bold text-emerald-700">Rp {{ number_format($openShift->expectedCash(), 0, ',', '.') }}</span></div>
                    </div>
                    <form method="POST" action="{{ route('shifts.close', $openShift) }}" class="space-y-3" x-data>
                        @csrf
                        <input type="hidden" name="expected_balance" value="{{ $openShift->expectedCash() }}">
                        <div>
                            <x-input-label for="closing_balance" value="Kas dihitung fisik (Rp)" />
                            <x-text-input id="closing_balance" name="closing_balance" type="number" min="0" required class="mt-1.5 form-input" x-ref="closing" />
                        </div>
                        <input type="text" name="note" placeholder="Catatan (opsional)" class="form-input">
                        <button class="w-full btn-danger">Tutup Shift</button>
                    </form>
                @else
                    <h3 class="section-title mb-4">Buka Shift Baru</h3>
                    <form method="POST" action="{{ route('shifts.open') }}" class="space-y-3">
                        @csrf
                        <div>
                            <x-input-label for="opening_balance" value="Modal awal kas (Rp)" />
                            <x-text-input id="opening_balance" name="opening_balance" type="number" min="0" value="0" required class="mt-1.5 form-input" />
                        </div>
                        <button class="w-full btn-primary">Buka Shift</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
