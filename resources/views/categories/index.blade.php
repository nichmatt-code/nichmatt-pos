<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-brand-700 dark:text-brand-300 tracking-tight">{{ __('Kategori') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Kelompokkan produk supaya lebih mudah dikelola.') }}</p>
    </x-slot>

    <livewire:categories.index />
</x-app-layout>
