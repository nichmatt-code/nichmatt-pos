<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $stockOpname->code }}</h3>
                @if ($stockOpname->isDraft())
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">{{ __('Berjalan') }}</span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">{{ __('Selesai') }}</span>
                @endif
            </div>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ $stockOpname->isForProducts() ? __('Stock opname Produk') : __('Stock opname Inventory') }}
                @if ($stockOpname->note)
                    &middot; {{ $stockOpname->note }}
                @endif
            </p>
        </div>
        <a href="{{ route('stock-opname.index') }}" wire:navigate class="text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">
            {{ __('Kembali ke daftar') }}
        </a>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500 bg-slate-50/70 dark:bg-slate-800/40">
                        <th class="px-6 py-3">{{ __('Item') }}</th>
                        <th class="px-6 py-3">{{ __('Stok Sistem') }}</th>
                        <th class="px-6 py-3">{{ __('Hasil Hitung Fisik') }}</th>
                        <th class="px-6 py-3">{{ __('Selisih') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($items as $item)
                        @php
                            $counted = $counts[$item->id] ?? '';
                            $difference = $counted === '' ? null : ((int) $counted - $item->system_qty);
                        @endphp
                        <tr wire:key="opname-item-{{ $item->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="px-6 py-3.5 text-slate-900 dark:text-slate-100 font-medium">{{ $item->item_name }}</td>
                            <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400">{{ $item->system_qty }} {{ $item->unit }}</td>
                            <td class="px-6 py-3.5">
                                @if ($stockOpname->isDraft())
                                    <input
                                        type="number"
                                        min="0"
                                        wire:model.blur="counts.{{ $item->id }}"
                                        placeholder="-"
                                        class="w-24 border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100"
                                    />
                                @else
                                    <span class="text-slate-900 dark:text-slate-100">{{ $item->counted_qty ?? '-' }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5">
                                @if ($difference === null)
                                    <span class="text-slate-400 dark:text-slate-500">{{ __('Belum dihitung') }}</span>
                                @elseif ($difference === 0)
                                    <span class="text-slate-500 dark:text-slate-400">0</span>
                                @elseif ($difference > 0)
                                    <span class="text-emerald-600 dark:text-emerald-400 font-medium">+{{ $difference }}</span>
                                @else
                                    <span class="text-rose-600 dark:text-rose-400 font-medium">{{ $difference }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">{{ __('Tidak ada item untuk dihitung.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($stockOpname->isDraft())
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                <x-primary-button wire:click="finish" wire:confirm="Selesaikan stock opname ini? Selisih stok akan langsung diterapkan.">
                    {{ __('Selesaikan Stock Opname') }}
                </x-primary-button>
            </div>
        @else
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 text-sm text-slate-500 dark:text-slate-400">
                {{ __('Diselesaikan oleh :name pada :date', ['name' => $stockOpname->completer?->name ?? '-', 'date' => optional($stockOpname->completed_at)->translatedFormat('d M Y H:i')]) }}
            </div>
        @endif
    </div>
</div>
