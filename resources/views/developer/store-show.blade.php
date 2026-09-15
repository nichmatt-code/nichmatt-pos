<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-slate-900 dark:text-slate-100 tracking-tight">{{ __('Developer — Detail Toko') }}</h2>
    </x-slot>

    <livewire:developer.store-show :store="$store" />
</x-app-layout>
