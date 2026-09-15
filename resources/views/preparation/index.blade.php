<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-slate-900 dark:text-slate-100 tracking-tight">{{ __('Persiapan Pesanan') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Pesanan yang sudah dibayar dan siap diproses.') }}</p>
    </x-slot>

    <livewire:preparation.index />
</x-app-layout>
