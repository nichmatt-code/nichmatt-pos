@props(['active'])
@php
    $tabs = [
        'sales' => ['route' => 'reports.sales', 'label' => 'Penjualan'],
        'stock-opname' => ['route' => 'reports.stock-opname', 'label' => 'Stock Opname'],
        'inventory-monitor' => ['route' => 'reports.inventory-monitor', 'label' => 'Monitor Inventory'],
        'inventory-loss' => ['route' => 'reports.inventory-loss', 'label' => 'Inventory Hilang'],
        'loss-history' => ['route' => 'reports.loss-history', 'label' => 'Riwayat Kerugian'],
    ];
@endphp
<div class="mb-5 flex flex-wrap gap-1.5">
    @foreach ($tabs as $key => $tab)
        <a href="{{ route($tab['route']) }}" wire:navigate
            class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ $active === $key ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : 'text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800' }}">
            {{ __($tab['label']) }}
        </a>
    @endforeach
</div>
