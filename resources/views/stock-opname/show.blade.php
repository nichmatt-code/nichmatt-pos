<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-lg text-brand-700 dark:text-brand-300 tracking-tight">{{ __('Stock Opname') }}</h2>
    </x-slot>

    <livewire:stock-opname.show :stock-opname="$stockOpname" />
</x-app-layout>
