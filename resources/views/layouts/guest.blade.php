<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Kasirku') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen bg-slate-900 flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4">
            <!-- Subtle grid backdrop -->
            <div class="pointer-events-none fixed inset-0 opacity-[0.06]"
                 style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 40px 40px;"></div>
            <div class="pointer-events-none fixed -top-40 left-1/2 -translate-x-1/2 h-96 w-96 rounded-full bg-emerald-500/20 blur-3xl"></div>

            <div class="relative z-10 w-full sm:max-w-md">
                <div class="mb-6 flex justify-center">
                    <a href="/" class="flex items-center gap-3">
                        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/40">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v9.75a3 3 0 0 0 3 3h4.5a3 3 0 0 0 3-3V3M6.75 3h10.5M6.75 3V2.25A.75.75 0 0 1 7.5 1.5h9a.75.75 0 0 1 .75.75V3m-12 18v-6.75M6 21h12m0 0V14.25" />
                            </svg>
                        </span>
                        <span class="text-3xl font-bold tracking-tight text-white">
                            Kasir<span class="text-emerald-400">ku</span>
                        </span>
                    </a>
                </div>

                <div class="px-6 py-6 bg-white shadow-2xl shadow-black/30 overflow-hidden rounded-2xl">
                    {{ $slot }}
                </div>

                <p class="mt-4 text-center text-xs text-slate-500">
                    Sistem Kasir &amp; Manajemen Toko
                </p>
            </div>
        </div>
    </body>
</html>
