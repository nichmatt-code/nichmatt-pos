<div class="space-y-6">
    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-5 flex flex-wrap items-end gap-4">
        <div>
            <x-input-label for="startDate" value="Dari Tanggal" />
            <input wire:model.live="startDate" id="startDate" type="date" class="mt-1 border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100" />
        </div>
        <div>
            <x-input-label for="endDate" value="Sampai Tanggal" />
            <input wire:model.live="endDate" id="endDate" type="date" class="mt-1 border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6">
            <div class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Total Omzet') }}</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900 dark:text-slate-100 tracking-tight">Rp {{ number_format($totalOmzet, 0, ',', '.') }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6">
            <div class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Jumlah Transaksi') }}</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900 dark:text-slate-100 tracking-tight">{{ $totalTransactions }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6">
            <div class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Estimasi Laba') }}</div>
            <div class="mt-1 text-2xl font-semibold text-emerald-600 dark:text-emerald-400 tracking-tight">Rp {{ number_format($totalProfit, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
            <div class="p-6 pb-0">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Produk Terlaris') }}</h3>
            </div>
            <div class="p-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">
                            <th class="pb-2 pr-4">{{ __('Produk') }}</th>
                            <th class="pb-2 pr-4">{{ __('Terjual') }}</th>
                            <th class="pb-2">{{ __('Omzet') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($bestSellers as $row)
                            <tr>
                                <td class="py-2.5 pr-4 text-slate-900 dark:text-slate-100 font-medium">{{ $row['name'] }}</td>
                                <td class="py-2.5 pr-4 text-slate-500 dark:text-slate-400">{{ $row['qty'] }}</td>
                                <td class="py-2.5 text-slate-500 dark:text-slate-400">Rp {{ number_format($row['revenue'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-8 text-center text-slate-400 dark:text-slate-500">{{ __('Belum ada penjualan.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
            <div class="p-6 pb-0 flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Riwayat Transaksi') }}</h3>
                <div class="relative w-full sm:w-56">
                    <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <x-text-input wire:model.live.debounce.300ms="search" type="text" class="w-full pl-9 text-sm" placeholder="Cari no. transaksi/customer..." />
                </div>
            </div>
            <div class="p-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">
                            <x-th-sort field="transaction_no" label="{{ __('No. Transaksi') }}" :sortField="$sortField" :sortDirection="$sortDirection" class="pb-2 pr-4" />
                            <th class="pb-2 pr-4">{{ __('Customer') }}</th>
                            <x-th-sort field="created_at" label="{{ __('Waktu') }}" :sortField="$sortField" :sortDirection="$sortDirection" class="pb-2 pr-4" />
                            <x-th-sort field="total" label="{{ __('Total') }}" :sortField="$sortField" :sortDirection="$sortDirection" class="pb-2 pr-4" />
                            <th class="pb-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($transactions as $transaction)
                            <tr>
                                <td class="py-2.5 pr-4 text-slate-900 dark:text-slate-100 font-medium">{{ $transaction->transaction_no }}</td>
                                <td class="py-2.5 pr-4 text-slate-500 dark:text-slate-400">{{ $transaction->customer_name ?? '-' }}</td>
                                <td class="py-2.5 pr-4 text-slate-500 dark:text-slate-400">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-2.5 pr-4 text-slate-900 dark:text-slate-100">Rp {{ number_format($transaction->total, 0, ',', '.') }}</td>
                                <td class="py-2.5 text-right whitespace-nowrap">
                                    <a href="{{ route('transactions.receipt', $transaction) }}" target="_blank" class="text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300 font-medium">
                                        {{ __('Cetak Struk') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400 dark:text-slate-500">{{ __('Belum ada transaksi.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 pb-6">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
        <div class="p-6 pb-0">
            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Penggunaan Inventory') }}</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Total bahan baku yang terpakai dari penjualan pada rentang tanggal ini.') }}</p>
        </div>
        <div class="p-6 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">
                        <th class="pb-2 pr-4">{{ __('Bahan') }}</th>
                        <th class="pb-2">{{ __('Terpakai') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($inventoryUsage as $row)
                        <tr>
                            <td class="py-2.5 pr-4 text-slate-900 dark:text-slate-100 font-medium">{{ $row['name'] }}</td>
                            <td class="py-2.5 text-slate-500 dark:text-slate-400">{{ $row['qty'] }} {{ $row['unit'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="py-8 text-center text-slate-400 dark:text-slate-500">{{ __('Belum ada bahan yang terpakai.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
