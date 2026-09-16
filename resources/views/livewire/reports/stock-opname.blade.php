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
            <x-text-input wire:model.live.debounce.300ms="search" type="text" class="mt-1 w-full pl-9 text-sm" placeholder="Kode atau catatan..." />
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
        <div class="p-6 pb-0">
            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Riwayat Stock Opname') }}</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Sesi stock opname yang sudah selesai dalam rentang tanggal ini, beserta selisih hasil hitung fisik vs sistem.') }}</p>
        </div>
        <div class="p-6 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">
                        <x-th-sort field="code" label="{{ __('Kode') }}" :sortField="$sortField" :sortDirection="$sortDirection" class="pb-2 pr-4" />
                        <x-th-sort field="type" label="{{ __('Tipe') }}" :sortField="$sortField" :sortDirection="$sortDirection" class="pb-2 pr-4" />
                        <x-th-sort field="completed_at" label="{{ __('Selesai') }}" :sortField="$sortField" :sortDirection="$sortDirection" class="pb-2 pr-4" />
                        <th class="pb-2 pr-4">{{ __('Selisih') }}</th>
                        <th class="pb-2 pr-4">{{ __('Diselesaikan Oleh') }}</th>
                        <th class="pb-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($opnames as $opname)
                        <tr>
                            <td class="py-2.5 pr-4 text-slate-900 dark:text-slate-100 font-medium">{{ $opname->code }}</td>
                            <td class="py-2.5 pr-4 text-slate-500 dark:text-slate-400">{{ $opname->isForProducts() ? __('Produk') : __('Inventory') }}</td>
                            <td class="py-2.5 pr-4 text-slate-500 dark:text-slate-400">{{ $opname->completed_at?->format('d/m/Y H:i') }}</td>
                            <td class="py-2.5 pr-4">
                                @if ($opname->net_difference == 0 && $opname->shrinkage_count === 0 && $opname->surplus_count === 0)
                                    <span class="text-slate-400 dark:text-slate-500">{{ __('Sesuai') }}</span>
                                @else
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @if ($opname->shrinkage_count > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400">
                                                {{ $opname->shrinkage_count }} {{ __('kurang') }}
                                            </span>
                                        @endif
                                        @if ($opname->surplus_count > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                                {{ $opname->surplus_count }} {{ __('lebih') }}
                                            </span>
                                        @endif
                                        <span class="text-xs text-slate-400 dark:text-slate-500">({{ $opname->net_difference > 0 ? '+' : '' }}{{ $opname->net_difference }} {{ __('net') }})</span>
                                    </div>
                                @endif
                            </td>
                            <td class="py-2.5 pr-4 text-slate-500 dark:text-slate-400">{{ $opname->completer?->name ?? '-' }}</td>
                            <td class="py-2.5 text-right whitespace-nowrap">
                                <a href="{{ route('stock-opname.show', $opname) }}" wire:navigate class="text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300 font-medium">
                                    {{ __('Lihat Detail') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 dark:text-slate-500">{{ __('Belum ada sesi stock opname yang selesai pada rentang ini.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 pb-6">
            {{ $opnames->links() }}
        </div>
    </div>
</div>
