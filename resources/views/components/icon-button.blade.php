@props(['title', 'color' => 'slate', 'href' => null])
@php
    $colors = [
        'slate' => 'text-slate-500 hover:text-slate-800 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-slate-100 dark:hover:bg-slate-800',
        'brand' => 'text-brand-600 hover:text-brand-800 hover:bg-brand-50 dark:text-brand-400 dark:hover:text-brand-300 dark:hover:bg-brand-500/10',
        'emerald' => 'text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:text-emerald-300 dark:hover:bg-emerald-500/10',
        'rose' => 'text-rose-600 hover:text-rose-800 hover:bg-rose-50 dark:text-rose-400 dark:hover:text-rose-300 dark:hover:bg-rose-500/10',
        'amber' => 'text-amber-600 hover:text-amber-800 hover:bg-amber-50 dark:text-amber-400 dark:hover:text-amber-300 dark:hover:bg-amber-500/10',
    ];
    $class = 'inline-flex items-center justify-center w-8 h-8 rounded-lg transition '.($colors[$color] ?? $colors['slate']);
@endphp
@if ($href)
    <a href="{{ $href }}" title="{{ $title }}" {{ $attributes->merge(['class' => $class]) }}>
        {{ $slot }}
        <span class="sr-only">{{ $title }}</span>
    </a>
@else
    <button type="button" title="{{ $title }}" {{ $attributes->merge(['class' => $class]) }}>
        {{ $slot }}
        <span class="sr-only">{{ $title }}</span>
    </button>
@endif
