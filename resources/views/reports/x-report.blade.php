<x-app-layout>
    <x-slot name="bodyClass">print-report</x-slot>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">X-Report &amp; Z-Report</h2>
            <p class="text-sm text-gray-500">Rekap penjualan per kasir untuk tutup kas harian.</p>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto px-4 py-6 sm:px-6 lg:px-8 space-y-5">
        <div class="page-card p-5 flex flex-wrap items-end gap-3">
            <form method="GET" action="{{ route('reports.x') }}" class="flex items-end gap-3">
                <div>
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="date" value="{{ $date->toDateString() }}" class="form-input">
                </div>
                <button class="btn-secondary">Tampilkan X</button>
            </form>
            <a href="{{ route('reports.z', ['date' => $date->toDateString()]) }}" class="btn-secondary">Buka Z-Report</a>
            <div class="ml-auto flex gap-2">
                <button type="button" onclick="window.print()" class="btn-primary">Cetak</button>
            </div>
        </div>

        @include('reports.partials.report-body', ['date' => $date, 'data' => $data, 'title' => 'X-Report'])
    </div>
</x-app-layout>