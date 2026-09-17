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
        <div class="relative flex-1 min-w-[200px]">
            <x-input-label value="Cari" />
            <svg class="pointer-events-none absolute left-3 top-[calc(50%+3px)] h-3.5 w-3.5 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <x-text-input wire:model.live.debounce.300ms="search" type="text" class="mt-1 w-full pl-9 text-sm" placeholder="No. kerugian atau alasan..." />
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6">
        <div class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Total Nilai Kerugian Tercatat') }}</div>
        <div class="mt-1 text-2xl font-semibold text-rose-600 dark:text-rose-400 tracking-tight">Rp {{ number_format($totalValue, 0, ',', '.') }}</div>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
        <div class="p-6 pb-0">
            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Daftar Kerugian') }}</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Semua kerugian yang dicatat lewat tombol "Catat Kerugian" di Kasir (barang rusak, makanan jatuh, dll).') }}</p>
        </div>
        <div class="p-6 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">
                        <th class="pb-2 pr-4">{{ __('Tanggal') }}</th>
                        <th class="pb-2 pr-4">{{ __('No. Kerugian') }}</th>
                        <th class="pb-2 pr-4">{{ __('Alasan') }}</th>
                        <th class="pb-2 pr-4">{{ __('Item') }}</th>
                        <th class="pb-2 pr-4">{{ __('Dicatat Oleh') }}</th>
                        <th class="pb-2 pr-4">{{ __('Total Nilai') }}</th>
                        <th class="pb-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($records as $record)
                        <tr>
                            <td class="py-2.5 pr-4 text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ $record->created_at->format('d/m/Y H:i') }}</td>
                            <td class="py-2.5 pr-4 text-slate-900 dark:text-slate-100 font-medium">{{ $record->loss_no }}</td>
                            <td class="py-2.5 pr-4 text-slate-500 dark:text-slate-400">{{ $record->reason }}</td>
                            <td class="py-2.5 pr-4 text-slate-500 dark:text-slate-400">{{ $record->items->pluck('product_name')->join(', ') }}</td>
                            <td class="py-2.5 pr-4 text-slate-500 dark:text-slate-400">{{ $record->user?->name ?? '-' }}</td>
                            <td class="py-2.5 pr-4 font-medium text-rose-600 dark:text-rose-400">Rp {{ number_format($record->total_cost_value, 0, ',', '.') }}</td>
                            <td class="py-2.5 text-right whitespace-nowrap">
                                <a href="{{ route('loss-records.receipt', $record) }}" target="_blank" class="text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300 font-medium">
                                    {{ __('Lihat Nota') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 dark:text-slate-500">{{ __('Belum ada kerugian tercatat pada rentang ini.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 pb-6">
            {{ $records->links() }}
        </div>
    </div>
</div>
