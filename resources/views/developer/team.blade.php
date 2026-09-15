<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-slate-900 dark:text-slate-100 tracking-tight">{{ __('Developer — Akses') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Kelola siapa saja yang punya akses developer.') }}</p>
    </x-slot>

    <livewire:developer.team />
</x-app-layout>
