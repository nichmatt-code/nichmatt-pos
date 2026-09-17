<div class="space-y-6">
    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
        <div class="p-6 pb-0">
            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Ringkasan') }}</h3>
        </div>
        <div class="p-6 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">
                        <th class="pb-3 pr-4">{{ __('Metrik') }}</th>
                        <th class="pb-3 pr-4">{{ __('Hari Ini') }}</th>
                        <th class="pb-3 pr-4">{{ __('Minggu Ini') }}</th>
                        <th class="pb-3">{{ __('Bulan Ini') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr>
                        <td class="py-3 pr-4 text-slate-500 dark:text-slate-400">{{ __('Omzet') }}</td>
                        <td class="py-3 pr-4 font-semibold text-slate-900 dark:text-slate-100">Rp {{ number_format($summary['today']['omzet'], 0, ',', '.') }}</td>
                        <td class="py-3 pr-4 font-semibold text-slate-900 dark:text-slate-100">Rp {{ number_format($summary['week']['omzet'], 0, ',', '.') }}</td>
                        <td class="py-3 font-semibold text-slate-900 dark:text-slate-100">Rp {{ number_format($summary['month']['omzet'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="py-3 pr-4 text-slate-500 dark:text-slate-400">{{ __('Jumlah Transaksi') }}</td>
                        <td class="py-3 pr-4 text-slate-900 dark:text-slate-100">{{ number_format($summary['today']['count'], 0, ',', '.') }}</td>
                        <td class="py-3 pr-4 text-slate-900 dark:text-slate-100">{{ number_format($summary['week']['count'], 0, ',', '.') }}</td>
                        <td class="py-3 text-slate-900 dark:text-slate-100">{{ number_format($summary['month']['count'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="py-3 pr-4 text-slate-500 dark:text-slate-400">{{ __('Kerugian Tercatat') }}</td>
                        <td class="py-3 pr-4 {{ $summary['today']['loss'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-slate-100' }}">Rp {{ number_format($summary['today']['loss'], 0, ',', '.') }}</td>
                        <td class="py-3 pr-4 {{ $summary['week']['loss'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-slate-100' }}">Rp {{ number_format($summary['week']['loss'], 0, ',', '.') }}</td>
                        <td class="py-3 {{ $summary['month']['loss'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-slate-100' }}">Rp {{ number_format($summary['month']['loss'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="py-3 pr-4 text-slate-500 dark:text-slate-400">{{ __('Inventory Hilang') }}</td>
                        <td class="py-3 pr-4 {{ $summary['today']['inventory_lost'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-slate-100' }}">Rp {{ number_format($summary['today']['inventory_lost'], 0, ',', '.') }}</td>
                        <td class="py-3 pr-4 {{ $summary['week']['inventory_lost'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-slate-100' }}">Rp {{ number_format($summary['week']['inventory_lost'], 0, ',', '.') }}</td>
                        <td class="py-3 {{ $summary['month']['inventory_lost'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-slate-100' }}">Rp {{ number_format($summary['month']['inventory_lost'], 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
            <p class="mt-3 text-xs text-slate-400 dark:text-slate-500">{{ __('Kerugian Tercatat = dicatat lewat "Catat Kerugian" di Kasir. Inventory Hilang = selisih hasil Stock Opname. Detail lengkap ada di Laporan → Monitor Inventory.') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6">
            <div class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Akses Cepat') }}</div>
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('pos') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-brand-600 rounded-lg font-medium text-sm text-white shadow-sm hover:bg-brand-700 transition">
                    {{ __('Buka Kasir') }}
                </a>
                <a href="{{ route('products.index') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg font-medium text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                    {{ __('Kelola Produk') }}
                </a>
                <a href="{{ route('reports.sales') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg font-medium text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                    {{ __('Laporan') }}
                </a>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl">
            <div class="p-6">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Produk Terlaris Bulan Ini') }}</h3>

                @if ($bestSellers->isEmpty())
                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ __('Belum ada penjualan bulan ini.') }}</p>
                @else
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                    <th class="pb-2 pr-4">{{ __('Produk') }}</th>
                                    <th class="pb-2 pr-4">{{ __('Terjual') }}</th>
                                    <th class="pb-2">{{ __('Omzet') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($bestSellers as $row)
                                    <tr>
                                        <td class="py-2 pr-4 text-slate-900 dark:text-slate-100 font-medium">{{ $row['name'] }}</td>
                                        <td class="py-2 pr-4 text-slate-500 dark:text-slate-400">{{ $row['qty'] }}</td>
                                        <td class="py-2 text-slate-500 dark:text-slate-400">Rp {{ number_format($row['revenue'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Tren Penjualan 14 Hari Terakhir') }}</h3>
            <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('Total') }}: Rp {{ number_format(collect($salesTrend)->sum('total'), 0, ',', '.') }}</span>
        </div>

        @php
            $maxTotal = max(1, collect($salesTrend)->max('total'));
            $barCount = count($salesTrend);
            $gap = 4;
            $barWidth = (700 - ($barCount - 1) * $gap) / $barCount;
        @endphp

        <div class="mt-4 overflow-x-auto">
            <div class="relative" x-data="{ tooltip: null }" x-on:mouseleave="tooltip = null">
                <svg viewBox="0 0 700 180" class="w-full h-40" preserveAspectRatio="none" role="img" aria-label="{{ __('Grafik penjualan 14 hari terakhir') }}">
                    @foreach ($salesTrend as $i => $day)
                        @php
                            $barHeight = $day['total'] > 0 ? max(2, ($day['total'] / $maxTotal) * 150) : 1;
                            $x = $i * ($barWidth + $gap);
                            $y = 150 - $barHeight;
                            $isToday = $day['date']->isToday();
                            $label = $day['date']->translatedFormat('d M Y');
                            $value = 'Rp '.number_format($day['total'], 0, ',', '.');
                        @endphp
                        <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $barHeight }}" rx="3"
                            class="{{ $isToday ? 'fill-brand-600 dark:fill-brand-400' : 'fill-brand-200 dark:fill-brand-500/30' }} cursor-pointer transition-opacity hover:opacity-75"
                            x-on:mouseenter="tooltip = {
                                label: '{{ $label }}',
                                value: '{{ $value }}',
                                left: $el.getBoundingClientRect().left - $el.closest('.relative').getBoundingClientRect().left + $el.getBoundingClientRect().width / 2,
                                top: $el.getBoundingClientRect().top - $el.closest('.relative').getBoundingClientRect().top,
                            }">
                            <title>{{ $label }}: {{ $value }}</title>
                        </rect>
                        <text x="{{ $x + $barWidth / 2 }}" y="170" text-anchor="middle" class="fill-slate-400 dark:fill-slate-500 pointer-events-none" style="font-size: 9px;">
                            {{ $day['date']->translatedFormat('d/M') }}
                        </text>
                    @endforeach
                </svg>

                <div x-show="tooltip" x-cloak
                    class="absolute z-10 -translate-x-1/2 -translate-y-full pointer-events-none rounded-lg bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 text-xs font-medium px-2.5 py-1.5 shadow-lg whitespace-nowrap"
                    :style="tooltip ? `left:${tooltip.left}px; top:${tooltip.top - 6}px` : ''">
                    <div class="text-slate-300 dark:text-slate-600" x-text="tooltip?.label"></div>
                    <div class="font-semibold" x-text="tooltip?.value"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl">
            <div class="p-6">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Pemakaian Inventory Hari Ini') }}</h3>

                @if ($inventoryUsageToday->isEmpty())
                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ __('Belum ada bahan yang terpakai hari ini.') }}</p>
                @else
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                    <th class="pb-3 pr-4">{{ __('Bahan') }}</th>
                                    <th class="pb-3">{{ __('Terpakai') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($inventoryUsageToday as $usage)
                                    <tr>
                                        <td class="py-2.5 pr-4 text-slate-900 dark:text-slate-100">{{ $usage->name }}</td>
                                        <td class="py-2.5 text-slate-500 dark:text-slate-400">{{ number_format($usage->used, 0, ',', '.') }} {{ $usage->unit }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl">
            <div class="p-6">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Inventory Menipis') }}</h3>

                @if ($lowStockInventory->isEmpty())
                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ __('Tidak ada bahan Inventory dengan stok menipis.') }}</p>
                @else
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                    <th class="pb-3 pr-4">{{ __('Bahan') }}</th>
                                    <th class="pb-3 pr-4">{{ __('Sisa Stok') }}</th>
                                    <th class="pb-3">{{ __('Min. Stok') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($lowStockInventory as $item)
                                    <tr>
                                        <td class="py-2.5 pr-4 text-slate-900 dark:text-slate-100">{{ $item->name }}</td>
                                        <td class="py-2.5 pr-4">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400">
                                                {{ $item->stock_qty }} {{ $item->unit }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 text-slate-500 dark:text-slate-400">{{ $item->min_stock }} {{ $item->unit }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl">
        <div class="p-6">
            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Stok Produk Menipis') }}</h3>

            @if ($lowStockProducts->isEmpty())
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ __('Tidak ada produk dengan stok menipis.') }}</p>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                <th class="pb-3 pr-4">{{ __('Produk') }}</th>
                                <th class="pb-3 pr-4">{{ __('Sisa Stok') }}</th>
                                <th class="pb-3">{{ __('Satuan') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($lowStockProducts as $product)
                                <tr>
                                    <td class="py-2.5 pr-4 text-slate-900 dark:text-slate-100">{{ $product->name }}</td>
                                    <td class="py-2.5 pr-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400">
                                            {{ $product->stock_qty }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-slate-500 dark:text-slate-400">{{ $product->unit }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
