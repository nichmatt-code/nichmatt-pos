<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-brand-700 dark:text-brand-300 tracking-tight">{{ __('Promo') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Diskon otomatis atau hadiah gratis untuk produk tertentu, aktif di Kasir dan Self Order.') }}</p>
    </x-slot>

    <livewire:promos.index />
</x-app-layout>
