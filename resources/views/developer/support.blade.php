<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-brand-700 dark:text-brand-300 tracking-tight">{{ __('Developer — Chat Bantuan') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Semua percakapan bantuan dari pengguna di semua toko. Balas untuk mengambil alih dari AI.') }}</p>
    </x-slot>

    <livewire:developer.support-inbox />
</x-app-layout>
