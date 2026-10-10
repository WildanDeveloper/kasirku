@php
    $nav = $navItems;
@endphp

<div class="flex flex-col h-full">
    <!-- Logo -->
    <div class="px-5 pt-5 pb-6">
        <a href="{{ route('dashboard') }}">
            <x-application-logo />
        </a>
    </div>

    <!-- Nav links -->
    <nav class="flex-1 px-3 space-y-1">
        @foreach ($nav as [$label, $routeName, $pattern, $path, $adminOnly])
            @continue($adminOnly && ! auth()->user()->is_admin)
            <a href="{{ route($routeName) }}"
               class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                {{ request()->routeIs($pattern)
                    ? 'bg-emerald-500/15 text-emerald-300'
                    : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
                </svg>
                {{ $label }}
            </a>
        @endforeach
    </nav>

    <!-- User -->
    <div class="p-3">
        <div x-data="dropdown" class="relative">
            <button @click="open = ! open"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-left hover:bg-slate-800 transition">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-sm font-bold text-white">
                    {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-white truncate">{{ Auth::user()->name }}</span>
                    <span class="block text-xs text-slate-400 truncate">{{ Auth::user()->email }}</span>
                </span>
                <svg class="h-4 w-4 text-slate-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </button>

            <div x-show="open" @click.outside="open = false" x-cloak
                 class="absolute bottom-full left-0 right-0 mb-2 rounded-lg bg-white shadow-xl ring-1 ring-gray-200 py-1 z-50">
                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Profil</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Keluar</button>
                </form>
            </div>
        </div>
    </div>
</div>
