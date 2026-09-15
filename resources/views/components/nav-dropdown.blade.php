@props(['label', 'active' => false])

@php
$triggerClasses = $active
    ? 'inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium text-brand-700 bg-brand-50 dark:text-brand-300 dark:bg-brand-500/10 transition duration-150 ease-in-out'
    : 'inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium text-slate-500 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-slate-100 dark:hover:bg-slate-800 transition duration-150 ease-in-out';
@endphp

<x-dropdown align="left" width="48">
    <x-slot name="trigger">
        <button type="button" class="{{ $triggerClasses }}">
            {{ $label }}
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
        </button>
    </x-slot>

    <x-slot name="content">
        {{ $slot }}
    </x-slot>
</x-dropdown>
