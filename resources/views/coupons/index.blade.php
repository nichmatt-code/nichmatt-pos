<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-brand-700 dark:text-brand-300 tracking-tight">{{ __('Kupon') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Kode kupon yang dimasukkan manual oleh kasir di Kasir - diskon dan/atau hadiah produk.') }}</p>
    </x-slot>

    <livewire:coupons.index />
</x-app-layout>
