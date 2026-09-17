<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-brand-700 dark:text-brand-300 tracking-tight">{{ __('Paket') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Gabungkan beberapa produk jadi satu paket dengan harga khusus.') }}</p>
    </x-slot>

    <livewire:packages.index />
</x-app-layout>
