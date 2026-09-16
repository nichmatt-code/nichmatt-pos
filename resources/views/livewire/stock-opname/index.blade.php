<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Hitung stok fisik Produk atau Inventory dan rekonsiliasi otomatis dengan sistem.') }}</p>
        <x-primary-button wire:click="openCreateModal">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('Mulai Stock Opname') }}
        </x-primary-button>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500 bg-slate-50/70 dark:bg-slate-800/40">
                        <th class="px-6 py-3">{{ __('Kode') }}</th>
                        <th class="px-6 py-3">{{ __('Tipe') }}</th>
                        <th class="px-6 py-3">{{ __('Item') }}</th>
                        <th class="px-6 py-3">{{ __('Status') }}</th>
                        <th class="px-6 py-3">{{ __('Dibuat Oleh') }}</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($opnames as $opname)
                        <tr wire:key="opname-{{ $opname->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="px-6 py-3.5 text-slate-900 dark:text-slate-100 font-medium">{{ $opname->code }}</td>
                            <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400">{{ $opname->isForProducts() ? __('Produk') : __('Inventory') }}</td>
                            <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400">{{ $opname->items_count }}</td>
                            <td class="px-6 py-3.5">
                                @if ($opname->isDraft())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">{{ __('Berjalan') }}</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">{{ __('Selesai') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400">{{ $opname->creator?->name ?? '-' }}</td>
                            <td class="px-6 py-3.5 text-right space-x-3 whitespace-nowrap">
                                <a href="{{ route('stock-opname.show', $opname) }}" wire:navigate class="text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300 font-medium">
                                    {{ $opname->isDraft() ? __('Lanjutkan') : __('Lihat') }}
                                </a>
                                @if ($opname->isDraft())
                                    <button wire:click="delete({{ $opname->id }})" wire:confirm="Hapus sesi stock opname ini?" class="text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300 font-medium">{{ __('Hapus') }}</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">{{ __('Belum ada sesi stock opname.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800">
            {{ $opnames->links() }}
        </div>
    </div>

    @if ($showCreateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-slate-900/60" wire:click="$set('showCreateModal', false)"></div>
        <div class="relative mb-6 bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-soft sm:max-w-md sm:mx-auto">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Mulai Stock Opname') }}</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Sistem akan mencatat stok saat ini sebagai acuan, lalu kamu tinggal isi hasil hitung fisiknya.') }}</p>

            <form wire:submit="create" class="mt-4 space-y-4">
                <div>
                    <x-input-label value="Hitung Stok Untuk" />
                    <div class="mt-1.5 grid grid-cols-2 gap-2">
                        <label class="flex items-center justify-center gap-2 px-3 py-2.5 rounded-lg border cursor-pointer text-sm font-medium transition {{ $type === 'product' ? 'bg-brand-50 border-brand-300 text-brand-700 dark:bg-brand-500/10 dark:border-brand-700 dark:text-brand-300' : 'bg-slate-50 border-slate-200 text-slate-500 dark:bg-slate-800/60 dark:border-slate-700 dark:text-slate-400' }}">
                            <input type="radio" wire:model.live="type" value="product" class="sr-only">
                            {{ __('Produk') }}
                        </label>
                        <label class="flex items-center justify-center gap-2 px-3 py-2.5 rounded-lg border cursor-pointer text-sm font-medium transition {{ $type === 'inventory' ? 'bg-brand-50 border-brand-300 text-brand-700 dark:bg-brand-500/10 dark:border-brand-700 dark:text-brand-300' : 'bg-slate-50 border-slate-200 text-slate-500 dark:bg-slate-800/60 dark:border-slate-700 dark:text-slate-400' }}">
                            <input type="radio" wire:model.live="type" value="inventory" class="sr-only">
                            {{ __('Inventory') }}
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="note" value="Catatan (opsional)" />
                    <x-text-input wire:model="note" id="note" type="text" class="block w-full" placeholder="mis. Opname akhir bulan" />
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">{{ __('Batal') }}</button>
                    <x-primary-button type="submit">{{ __('Mulai') }}</x-primary-button>
                </div>
            </form>
        </div>
        </div>
    </div>
    @endif
</div>
