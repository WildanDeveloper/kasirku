<x-app-layout>
    <x-slot name="bodyClass">print-report</x-slot>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Z-Report &mdash; Tutup Kas</h2>
            <p class="text-sm text-gray-500">Ringkasan final penjualan dan daftar shift kasir.</p>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto px-4 py-6 sm:px-6 lg:px-8 space-y-5">
        <div class="page-card p-5 flex flex-wrap items-end gap-3">
            <form method="GET" action="{{ route('reports.z') }}" class="flex items-end gap-3">
                <div>
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="date" value="{{ $date->toDateString() }}" class="form-input">
                </div>
                <button class="btn-secondary">Tampilkan</button>
            </form>
            <a href="{{ route('reports.x', ['date' => $date->toDateString()]) }}" class="btn-secondary">X-Report</a>
            <button type="button" onclick="window.print()" class="btn-primary ml-auto">Cetak</button>
        </div>

        @include('reports.partials.report-body', ['date' => $date, 'data' => $data, 'title' => 'Z-Report'])

        <div class="page-card overflow-hidden print:hidden">
            <div class="p-5 border-b border-gray-100">
                <h3 class="section-title">Shift Kas Tanggal Ini</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="table-th">Kasir</th>
                            <th class="table-th">Buka</th>
                            <th class="table-th">Tutup</th>
                            <th class="table-th text-right">Modal</th>
                            <th class="table-th text-right">Harapan</th>
                            <th class="table-th text-right">Aktual</th>
                            <th class="table-th text-right">Selisih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($shifts as $shift)
                            <tr class="hover:bg-gray-50">
                                <td class="table-td font-medium text-gray-900">{{ $shift->user?->name ?? '-' }}</td>
                                <td class="table-td text-gray-600 text-xs">{{ $shift->opened_at->format('d/m H:i') }}</td>
                                <td class="table-td text-gray-600 text-xs">{{ $shift->closed_at?->format('d/m H:i') ?? 'MASIH BUKA' }}</td>
                                <td class="table-td text-right">{{ number_format($shift->opening_amount, 0, ',', '.') }}</td>
                                <td class="table-td text-right font-semibold">{{ number_format($shift->expectedCash(), 0, ',', '.') }}</td>
                                <td class="table-td text-right">{{ $shift->closing_amount === null ? '-' : number_format($shift->closing_amount, 0, ',', '.') }}</td>
                                <td class="table-td text-right font-semibold {{ ($shift->difference ?? 0) < 0 ? 'text-rose-700' : (($shift->difference ?? 0) > 0 ? 'text-emerald-700' : 'text-gray-400') }}">
                                    {{ $shift->difference === null ? '-' : number_format($shift->difference, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="table-td text-center text-gray-400 py-8">Tidak ada shift pada tanggal ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>