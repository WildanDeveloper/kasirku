<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Log Aktivitas</h2>
            <p class="text-sm text-gray-500">Jejak perubahan penting: siapa, kapan, dan apa yang diubah.</p>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <div class="page-card overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex flex-wrap gap-2">
                <form method="GET" class="flex flex-wrap gap-2">
                    <select name="action" class="form-input sm:w-64">
                        <option value="">Semua jenis aktivitas</option>
                        @foreach ($actions as $key => $label)
                            <option value="{{ $key }}" @selected(request('action') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="user_id" class="form-input sm:w-48">
                        <option value="">Semua pengguna</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((int) request('user_id') === $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn-secondary">Filter</button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="table-th">Waktu</th>
                            <th class="table-th">Pengguna</th>
                            <th class="table-th">Aktivitas</th>
                            <th class="table-th">Keterangan</th>
                            <th class="table-th">Objek</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-gray-50 align-top">
                                <td class="table-td whitespace-nowrap text-gray-500">
                                    {{ $log->created_at->format('d/m/Y H:i:s') }}
                                </td>
                                <td class="table-td">
                                    <span class="font-medium text-gray-800">{{ $log->user?->name ?? 'Sistem' }}</span>
                                    @if ($log->ip_address)
                                        <span class="block text-[10px] text-gray-400 font-mono">{{ $log->ip_address }}</span>
                                    @endif
                                </td>
                                <td class="table-td">
                                    <span class="text-xs font-semibold px-2 py-1 rounded-full bg-slate-100 text-slate-700">
                                        {{ $log->label() }}
                                    </span>
                                </td>
                                <td class="table-td text-gray-700">{{ $log->description }}</td>
                                <td class="table-td text-gray-400 text-xs whitespace-nowrap">{{ $log->subjectLabel() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-12 text-center text-sm text-gray-500">Belum ada aktivitas tercatat.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">{{ $logs->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>