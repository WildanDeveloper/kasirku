@props(['compact' => false])

<a {{ $attributes }} class="flex items-center gap-2.5 select-none">
    <span class="flex items-center justify-center {{ $compact ? 'h-9 w-9' : 'h-10 w-10' }} rounded-xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/30">
        <svg class="{{ $compact ? 'h-5 w-5' : 'h-6 w-6' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v9.75a3 3 0 0 0 3 3h4.5a3 3 0 0 0 3-3V3M6.75 3h10.5M6.75 3V2.25A.75.75 0 0 1 7.5 1.5h9a.75.75 0 0 1 .75.75V3m-12 18v-6.75M6 21h12m0 0V14.25" />
        </svg>
    </span>
    @unless ($compact)
        <span class="text-xl font-bold tracking-tight">
            <span class="text-white">Kasir</span><span class="text-emerald-400">ku</span>
        </span>
    @endunless
</a>
