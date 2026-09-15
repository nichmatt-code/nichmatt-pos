<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-slate-900 dark:text-slate-100 tracking-tight">{{ __('Stock Opname') }}</h2>
    </x-slot>

    <livewire:stock-opname.show :stock-opname="$stockOpname" />
</x-app-layout>
