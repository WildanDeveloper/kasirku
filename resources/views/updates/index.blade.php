<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Apa Yang Baru</h2>
            <p class="text-sm text-gray-500">Rilis terbaru & pembaruan aplikasi Kasirku.</p>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 py-6 sm:px-6 lg:px-8 space-y-5">
        <div class="page-card p-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-2xl font-extrabold text-gray-900">v{{ $currentVersion }}</span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                            {{ $releasedAt }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">
                        @if ($updateAvailable && $latestVersion)
                            Versi <strong class="text-emerald-700">v{{ $latestVersion }}</strong> tersedia di server.
                        @else
                            Aplikasi Anda sudah versi terbaru.
                        @endif
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ $repository }}" target="_blank" rel="noopener" class="btn-secondary">Kode Sumber</a>
                    @if (auth()->user()->is_admin && $updateAvailable)
                        <form method="POST" action="{{ route('updates.apply') }}"
                              onsubmit="return confirm('Perbarui aplikasi sekarang? Halaman akan sempat tidak tersedia.')">
                            @csrf
                            <button class="btn-primary">Perbarui ke v{{ $latestVersion }}</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        @foreach ($changelog as $version => $release)
            <div class="page-card overflow-hidden">
                <div class="flex items-center justify-between p-5 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <h3 class="text-lg font-bold text-gray-900">v{{ $version }}</h3>
                        @if ($version === $currentVersion)
                            <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Saat ini</span>
                        @endif
                    </div>
                    <span class="text-xs text-gray-500">{{ $release['date'] ?? '' }}</span>
                </div>
                <ul class="p-5 space-y-2">
                    @foreach ($release['features'] ?? [] as $feature)
                        <li class="flex items-start gap-2 text-sm text-gray-700">
                            <svg class="h-4 w-4 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            <span>{{ $feature }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</x-app-layout>