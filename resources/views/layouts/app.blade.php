<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Kasir') — {{ config('app.name', 'Kasirku') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900">
        <div class="min-h-screen bg-gray-100 flex">
            <aside class="hidden lg:flex lg:flex-col w-64 shrink-0 bg-slate-900">
                @include('layouts.navigation')
            </aside>

            <div class="flex-1 flex flex-col min-w-0">
                <!-- Mobile top bar -->
                <div class="lg:hidden bg-slate-900 px-4 py-3 flex items-center justify-between">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo compact />
                    </a>
                    <div x-data="dropdown" class="relative">
                        <button @click="open = ! open" class="p-1.5 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </button>
                        <div x-show="open" @click.outside="open = false" x-cloak
                             class="absolute right-0 mt-2 w-48 rounded-lg bg-white shadow-lg ring-1 ring-gray-200 py-1 z-40">
                            <div class="px-4 py-2 border-b border-gray-100">
                                <div class="text-sm font-semibold text-gray-900">{{ Auth::user()->name }}</div>
                                <div class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</div>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Profil</a>
                            <form method="POST" action="{{ route('logout') }}" @submit.prevent="doLogout()">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Keluar</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Mobile nav tabs -->
                <div class="lg:hidden bg-white border-b border-gray-200 px-2 overflow-x-auto">
                    <nav class="flex gap-1 whitespace-nowrap">
                        @foreach ($navItems as [$label, $routeName, $pattern, $path, $adminOnly])
                            @continue($adminOnly && ! auth()->user()->is_admin)
                            <a href="{{ route($routeName) }}"
                               class="px-3 py-2.5 text-sm font-medium border-b-2 transition
                                {{ request()->routeIs($pattern)
                                    ? 'border-emerald-500 text-emerald-700'
                                    : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </nav>
                </div>

                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white border-b border-gray-200">
                        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="flex-1">
                    {{ $slot }}
                </main>
            </div>
        </div>

    </body>
</html>
