@props(['class' => ''])

<button
    type="button"
    x-data="{ isFullscreen: false }"
    x-init="document.addEventListener('fullscreenchange', () => { isFullscreen = !!document.fullscreenElement })"
    x-on:click="document.fullscreenElement ? document.exitFullscreen() : document.documentElement.requestFullscreen()"
    title="{{ __('Layar penuh') }}"
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center h-9 w-9 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:text-slate-500 dark:hover:text-slate-200 dark:hover:bg-slate-800 transition '.$class]) }}
>
    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" x-show="!isFullscreen">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V6a2 2 0 012-2h2M4 16v2a2 2 0 002 2h2M20 8V6a2 2 0 00-2-2h-2M20 16v2a2 2 0 01-2 2h-2" />
    </svg>
    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" x-show="isFullscreen" x-cloak>
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 4v3a1 1 0 01-1 1H5M4 9V6a2 2 0 012-2h2M15 20v-3a1 1 0 011-1h3M20 15v3a2 2 0 01-2 2h-2M9 20v-3a1 1 0 00-1-1H5M4 15v3a2 2 0 002 2h2M15 4v3a1 1 0 001 1h3M20 9V6a2 2 0 00-2-2h-2" />
    </svg>
</button>
