<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-brand-700 dark:text-brand-300 tracking-tight">{{ __('Riwayat Kerugian') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Daftar kerugian yang dicatat lewat "Catat Kerugian" di Kasir.') }}</p>
    </x-slot>

    <x-reports-tabs active="loss-history" />

    <livewire:reports.loss-history />
</x-app-layout>
