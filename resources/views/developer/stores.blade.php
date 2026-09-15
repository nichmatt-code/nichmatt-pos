<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-slate-900 dark:text-slate-100 tracking-tight">{{ __('Developer — Semua Toko') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Lihat semua toko lintas-tenant beserta status langganannya.') }}</p>
    </x-slot>

    <livewire:developer.stores />
</x-app-layout>
