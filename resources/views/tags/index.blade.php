<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-brand-700 dark:text-brand-300 tracking-tight">{{ __('Tags') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Kelola tag yang bisa dipasang ke produk untuk memudahkan pencarian.') }}</p>
    </x-slot>

    <livewire:tags.index />
</x-app-layout>
