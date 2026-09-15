<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-slate-900 dark:text-slate-100 tracking-tight">{{ __('Produk') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Kelola daftar produk dan stok toko Anda.') }}</p>
    </x-slot>

    <livewire:products.index />
</x-app-layout>
