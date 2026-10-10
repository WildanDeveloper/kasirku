<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('updates.index') }}" class="text-xs font-semibold text-emerald-600 hover:underline">← Apa Yang Baru</a>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Hasil Pembaruan</h2>
            <p class="text-sm text-gray-500">Log setiap langkah pembaruan aplikasi.</p>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 py-6 sm:px-6 lg:px-8 space-y-5">
        @php $success = collect($log)->every(fn ($row) => $row['ok']); @endphp

        <div class="rounded-xl border px-4 py-3 text-sm font-semibold {{ $success ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-amber-50 border-amber-200 text-amber-800' }}">
            {{ $success
                ? 'Pembaruan selesai. Versi aplikasi sekarang v'.$currentVersion.'.'
                : 'Pembaruan terhenti di tengah jalan. Periksa detail di bawah.' }}
        </div>

        <div class="page-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="table-th">Langkah</th>
                            <th class="table-th">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($log as $row)
                            <tr class="align-top">
                                <td class="table-td">
                                    <div class="font-medium text-gray-800">{{ $row['step'] }}</div>
                                    @if ($row['output'] !== '')
                                        <pre class="mt-1 text-[11px] leading-relaxed bg-slate-900 text-slate-100 rounded-lg p-2 overflow-x-auto max-h-40">{{ $row['output'] }}</pre>
                                    @endif
                                </td>
                                <td class="table-td">
                                    @if ($row['ok'])
                                        <span class="text-xs font-semibold px-2 py-1 rounded-full bg-emerald-100 text-emerald-700">Berhasil</span>
                                    @else
                                        <span class="text-xs font-semibold px-2 py-1 rounded-full bg-red-100 text-red-700">Gagal</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-center gap-2">
            <a href="{{ route('updates.index') }}" class="btn-secondary">Kembali</a>
            <a href="{{ route('pos.index') }}" class="btn-primary">Buka Kasir</a>
        </div>
    </div>
</x-app-layout>