<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-brand-700 dark:text-brand-300 tracking-tight">{{ __('Developer — Detail Toko') }}</h2>
    </x-slot>

    <livewire:developer.store-show :store="$store" />
</x-app-layout>
