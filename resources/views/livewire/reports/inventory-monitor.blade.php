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
            <div class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Total Nilai Kerugian') }}</div>
            <div class="mt-1 text-2xl font-semibold text-rose-600 dark:text-rose-400 tracking-tight">Rp {{ number_format($totalLossValue, 0, ',', '.') }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6">
            <div class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Total Bahan Terpakai') }}</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900 dark:text-slate-100 tracking-tight">{{ number_format($totalUsed, 0, ',', '.') }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6">
            <div class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Bahan Bermasalah') }}</div>
            <div class="mt-1 text-2xl font-semibold text-amber-600 dark:text-amber-400 tracking-tight">{{ $itemsWithShortage }}</div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
        <div class="p-6 pb-0">
            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Rincian per Bahan') }}</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Terpakai = dipakai lewat resep produk/paket yang terjual. Kerugian Tercatat = ditulis lewat "Catat Kerugian" di Kasir. Selisih Stock Opname = hasil hitung fisik dikurangi sistem (negatif berarti hilang).') }}</p>
        </div>
        <div class="p-6 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">
                        <th class="pb-2 pr-4">{{ __('Bahan') }}</th>
                        <th class="pb-2 pr-4">{{ __('Harga Modal') }}</th>
                        <th class="pb-2 pr-4">{{ __('Terpakai') }}</th>
                        <th class="pb-2 pr-4">{{ __('Kerugian Tercatat') }}</th>
                        <th class="pb-2 pr-4">{{ __('Selisih Stock Opname') }}</th>
                        <th class="pb-2">{{ __('Nilai Kerugian') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="py-2.5 pr-4 text-slate-900 dark:text-slate-100 font-medium">{{ $row['item']->name }}</td>
                            <td class="py-2.5 pr-4 text-slate-500 dark:text-slate-400">Rp {{ number_format($row['item']->cost_price, 0, ',', '.') }}</td>
                            <td class="py-2.5 pr-4 text-slate-500 dark:text-slate-400">{{ number_format($row['used'], 0, ',', '.') }} {{ $row['item']->unit }}</td>
                            <td class="py-2.5 pr-4">
                                @if ($row['recorded_loss'] > 0)
                                    <span class="text-rose-600 dark:text-rose-400">{{ number_format($row['recorded_loss'], 0, ',', '.') }} {{ $row['item']->unit }}</span>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500">-</span>
                                @endif
                            </td>
                            <td class="py-2.5 pr-4">
                                @if ($row['opname_difference'] < 0)
                                    <span class="text-rose-600 dark:text-rose-400">{{ number_format($row['opname_difference'], 0, ',', '.') }} {{ $row['item']->unit }}</span>
                                @elseif ($row['opname_difference'] > 0)
                                    <span class="text-emerald-600 dark:text-emerald-400">+{{ number_format($row['opname_difference'], 0, ',', '.') }} {{ $row['item']->unit }}</span>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500">-</span>
                                @endif
                            </td>
                            <td class="py-2.5 font-medium {{ $row['loss_value'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400 dark:text-slate-500' }}">
                                Rp {{ number_format($row['loss_value'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 dark:text-slate-500">{{ __('Belum ada barang inventory.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
