<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-brand-700 dark:text-brand-300 tracking-tight">{{ __('Satuan') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Daftar satuan yang bisa dipilih saat membuat atau mengedit Inventory.') }}</p>
    </x-slot>

    <livewire:units.index />
</x-app-layout>
