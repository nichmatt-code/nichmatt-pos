<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="relative w-full sm:w-72">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <x-text-input wire:model.live.debounce.300ms="search" type="text" class="w-full pl-10" placeholder="Cari bahan/barang..." />
        </div>
        <x-primary-button wire:click="createItem">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('Tambah Barang') }}
        </x-primary-button>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500 bg-slate-50/70 dark:bg-slate-800/40">
                        <th class="px-6 py-3">{{ __('Barang') }}</th>
                        <th class="px-6 py-3">{{ __('Stok') }}</th>
                        <th class="px-6 py-3">{{ __('Catatan') }}</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($items as $item)
                        <tr wire:key="inventory-{{ $item->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="px-6 py-3.5 text-slate-900 dark:text-slate-100 font-medium">
                                {{ $item->name }}
                                @if ($item->sku)
                                    <div class="text-xs font-normal text-slate-400 dark:text-slate-500">SKU: {{ $item->sku }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-3.5">
                                @if ($item->isLowStock())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400">
                                        {{ $item->stock_qty }} {{ $item->unit }}
                                    </span>
                                @else
                                    <span class="text-slate-900 dark:text-slate-100">{{ $item->stock_qty }} {{ $item->unit }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400">{{ $item->note ?: '-' }}</td>
                            <td class="px-6 py-3.5 text-right space-x-3 whitespace-nowrap">
                                <button wire:click="openStockModal({{ $item->id }})" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300 font-medium">{{ __('Stok') }}</button>
                                <button wire:click="editItem({{ $item->id }})" class="text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300 font-medium">{{ __('Edit') }}</button>
                                <button wire:click="delete({{ $item->id }})" wire:confirm="Hapus barang ini?" class="text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300 font-medium">{{ __('Hapus') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">{{ __('Belum ada barang inventory.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800">
            {{ $items->links() }}
        </div>
    </div>

    <!-- Item Form Modal -->
    @if ($showFormModal)
    <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-slate-900/60" wire:click="$set('showFormModal', false)"></div>
        <div class="relative mb-6 bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-soft sm:max-w-lg sm:mx-auto">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $editingId ? __('Edit Barang') : __('Tambah Barang') }}</h3>

            <form wire:submit="save" class="mt-4 space-y-4">
                <div>
                    <x-input-label for="name" value="Nama Barang" />
                    <x-text-input wire:model="name" id="name" type="text" class="block w-full" placeholder="mis. Beras, Minyak Goreng" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="sku" value="Kode (opsional)" />
                        <div class="mt-1 flex gap-2">
                            <x-text-input wire:model="sku" id="sku" type="text" class="block w-full" placeholder="Tulis atau generate" />
                            <button type="button" wire:click="generateSku" class="shrink-0 px-3 py-2 text-xs font-medium text-brand-700 bg-brand-50 rounded-lg hover:bg-brand-100 dark:text-brand-300 dark:bg-brand-500/10 dark:hover:bg-brand-500/20">{{ __('Generate') }}</button>
                        </div>
                        <x-input-error :messages="$errors->get('sku')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="unit" value="Satuan" />
                        <x-text-input wire:model="unit" id="unit" type="text" class="block w-full" placeholder="kg, liter, karung" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="stock_qty" value="Stok Saat Ini" />
                        <x-text-input wire:model="stock_qty" id="stock_qty" type="number" class="block w-full" @disabled($editingId) />
                        <x-input-error :messages="$errors->get('stock_qty')" class="mt-2" />
                        @if ($editingId)
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ __('Ubah lewat tombol Stok atau Stock Opname.') }}</p>
                        @endif
                    </div>
                    <div>
                        <x-input-label for="min_stock" value="Stok Minimum" />
                        <x-text-input wire:model="min_stock" id="min_stock" type="number" class="block w-full" />
                        <x-input-error :messages="$errors->get('min_stock')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="note" value="Catatan (opsional)" />
                    <x-text-input wire:model="note" id="note" type="text" class="block w-full" placeholder="mis. supplier, lokasi rak" />
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="$set('showFormModal', false)" class="px-4 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">{{ __('Batal') }}</button>
                    <x-primary-button type="submit">{{ __('Simpan') }}</x-primary-button>
                </div>
            </form>
        </div>
        </div>
    </div>
    @endif

    <!-- Stock Adjustment Modal -->
    @if ($showStockModal)
    <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-slate-900/60" wire:click="$set('showStockModal', false)"></div>
        <div class="relative mb-6 bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-soft sm:max-w-md sm:mx-auto">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Atur Stok') }}</h3>

            <form wire:submit="saveStock" class="mt-4 space-y-4">
                <div>
                    <x-input-label for="stockType" value="Jenis" />
                    <select wire:model="stockType" id="stockType" class="block w-full mt-1 border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100">
                        <option value="in">{{ __('Stok Masuk') }}</option>
                        <option value="out">{{ __('Stok Keluar') }}</option>
                        <option value="adjustment">{{ __('Set Stok ke Jumlah Ini') }}</option>
                    </select>
                </div>

                <div>
                    <x-input-label for="stockQty" value="Jumlah" />
                    <x-text-input wire:model="stockQty" id="stockQty" type="number" class="block w-full" />
                    <x-input-error :messages="$errors->get('stockQty')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="stockNote" value="Catatan (opsional)" />
                    <x-text-input wire:model="stockNote" id="stockNote" type="text" class="block w-full" />
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="$set('showStockModal', false)" class="px-4 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">{{ __('Batal') }}</button>
                    <x-primary-button type="submit">{{ __('Simpan') }}</x-primary-button>
                </div>
            </form>
        </div>
        </div>
    </div>
    @endif
</div>
