<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Notifikasi</h2>
            <p class="text-sm text-gray-500">Peringatan stok menipis, utang jatuh tempo, dan shift yang belum ditutup.</p>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 py-6 sm:px-6 lg:px-8 space-y-5">
        @if (session('success'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500">{{ $notifications->total() }} notifikasi</p>
            <form method="POST" action="{{ route('notifications.read_all') }}">
                @csrf
                <button class="btn-secondary !py-1.5">Tandai semua dibaca</button>
            </form>
        </div>

        <div class="space-y-3">
            @forelse ($notifications as $notification)
                @php
                    $tone = match ($notification->level) {
                        'danger' => 'border-rose-200 bg-rose-50',
                        'warning' => 'border-amber-200 bg-amber-50',
                        default => 'border-gray-200 bg-white',
                    };
                    $dot = match ($notification->level) {
                        'danger' => 'bg-rose-500',
                        'warning' => 'bg-amber-500',
                        default => 'bg-blue-500',
                    };
                @endphp
                <div class="page-card p-4 flex items-start gap-3 border {{ $tone }} {{ $notification->read_at ? 'opacity-70' : '' }}">
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $notification->read_at ? 'bg-gray-300' : $dot }}"></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-semibold text-gray-900">{{ $notification->title }}</h3>
                            @unless ($notification->read_at)
                                <span class="badge bg-blue-100 text-blue-700">Baru</span>
                            @endunless
                        </div>
                        <p class="mt-0.5 text-sm text-gray-600">{{ $notification->message }}</p>
                        <p class="mt-1 text-[11px] text-gray-400">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        @if ($notification->url)
                            <a href="{{ $notification->url }}" class="btn-ghost">Lihat</a>
                        @endif
                        <form method="POST" action="{{ route('notifications.destroy', $notification) }}">
                            @csrf @method('DELETE')
                            <button class="btn-ghost !text-rose-600">Hapus</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="page-card p-10 text-center text-sm text-gray-400">Tidak ada notifikasi.</div>
            @endforelse
        </div>

        <div>{{ $notifications->links() }}</div>
    </div>
</x-app-layout>