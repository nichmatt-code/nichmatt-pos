<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-slate-900 tracking-tight">{{ __('Kategori') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Kelompokkan produk supaya lebih mudah dikelola.') }}</p>
    </x-slot>

    <livewire:categories.index />
</x-app-layout>
