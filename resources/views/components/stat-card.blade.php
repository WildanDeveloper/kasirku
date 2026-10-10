@props([
    'label',
    'value',
    'icon' => null,
    'tone' => 'gray',
])

@php
    $tones = [
        'gray'    => 'bg-gray-100 text-gray-600',
        'emerald' => 'bg-emerald-100 text-emerald-600',
        'blue'    => 'bg-blue-100 text-blue-600',
        'amber'   => 'bg-amber-100 text-amber-600',
        'red'     => 'bg-red-100 text-red-600',
    ];
@endphp

<div class="page-card p-5">
    <div class="flex items-center gap-4">
        @if ($icon)
            <span class="flex h-11 w-11 items-center justify-center rounded-lg {{ $tones[$tone] }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                </svg>
            </span>
        @endif
        <div class="min-w-0">
            <div class="text-sm text-gray-500">{{ $label }}</div>
            <div class="mt-0.5 text-2xl font-bold text-gray-900 truncate">{{ $value }}</div>
        </div>
    </div>
</div>
