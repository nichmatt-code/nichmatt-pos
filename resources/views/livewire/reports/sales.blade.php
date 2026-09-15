<div class="space-y-6">
    <div class="bg-white border border-slate-200/70 shadow-card rounded-2xl p-5 flex flex-wrap items-end gap-4">
        <div>
            <x-input-label for="startDate" value="Dari Tanggal" />
            <input wire:model.live="startDate" id="startDate" type="date" class="mt-1 border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900" />
        </div>
        <div>
            <x-input-label for="endDate" value="Sampai Tanggal" />
            <input wire:model.live="endDate" id="endDate" type="date" class="mt-1 border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white border border-slate-200/70 shadow-card rounded-2xl p-6">
            <div class="text-sm font-medium text-slate-500">{{ __('Total Omzet') }}</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900 tracking-tight">Rp {{ number_format($totalOmzet, 0, ',', '.') }}</div>
        </div>
        <div class="bg-white border border-slate-200/70 shadow-card rounded-2xl p-6">
            <div class="text-sm font-medium text-slate-500">{{ __('Jumlah Transaksi') }}</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900 tracking-tight">{{ $totalTransactions }}</div>
        </div>
        <div class="bg-white border border-slate-200/70 shadow-card rounded-2xl p-6">
            <div class="text-sm font-medium text-slate-500">{{ __('Estimasi Laba') }}</div>
            <div class="mt-1 text-2xl font-semibold text-emerald-600 tracking-tight">Rp {{ number_format($totalProfit, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white border border-slate-200/70 shadow-card rounded-2xl overflow-hidden">
            <div class="p-6 pb-0">
                <h3 class="text-base font-semibold text-slate-900">{{ __('Produk Terlaris') }}</h3>
            </div>
            <div class="p-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400">
                            <th class="pb-2 pr-4">{{ __('Produk') }}</th>
                            <th class="pb-2 pr-4">{{ __('Terjual') }}</th>
                            <th class="pb-2">{{ __('Omzet') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($bestSellers as $row)
                            <tr>
                                <td class="py-2.5 pr-4 text-slate-900 font-medium">{{ $row['name'] }}</td>
                                <td class="py-2.5 pr-4 text-slate-500">{{ $row['qty'] }}</td>
                                <td class="py-2.5 text-slate-500">Rp {{ number_format($row['revenue'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-8 text-center text-slate-400">{{ __('Belum ada penjualan.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white border border-slate-200/70 shadow-card rounded-2xl overflow-hidden">
            <div class="p-6 pb-0">
                <h3 class="text-base font-semibold text-slate-900">{{ __('Riwayat Transaksi') }}</h3>
            </div>
            <div class="p-6 overflow-x-auto max-h-96 overflow-y-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400">
                            <th class="pb-2 pr-4">{{ __('No. Transaksi') }}</th>
                            <th class="pb-2 pr-4">{{ __('Waktu') }}</th>
                            <th class="pb-2">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($transactions as $transaction)
                            <tr>
                                <td class="py-2.5 pr-4">
                                    <a href="{{ route('transactions.receipt', $transaction) }}" target="_blank" class="text-brand-600 hover:text-brand-800 font-medium">
                                        {{ $transaction->transaction_no }}
                                    </a>
                                </td>
                                <td class="py-2.5 pr-4 text-slate-500">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-2.5 text-slate-900">Rp {{ number_format($transaction->total, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-8 text-center text-slate-400">{{ __('Belum ada transaksi.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
