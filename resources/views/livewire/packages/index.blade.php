<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="relative w-full sm:w-72">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <x-text-input wire:model.live.debounce.300ms="search" type="text" class="w-full pl-10" placeholder="Cari paket..." />
        </div>
        <x-primary-button wire:click="createPackage">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('Tambah Paket') }}
        </x-primary-button>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500 bg-slate-50/70 dark:bg-slate-800/40">
                        <th class="px-6 py-3"></th>
                        <x-th-sort field="name" label="{{ __('Paket') }}" :sortField="$sortField" :sortDirection="$sortDirection" />
                        <th class="px-6 py-3">{{ __('Isi') }}</th>
                        <x-th-sort field="price" label="{{ __('Harga') }}" :sortField="$sortField" :sortDirection="$sortDirection" />
                        <th class="px-6 py-3">{{ __('Status') }}</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($packages as $package)
                        <tr wire:key="package-{{ $package->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="pl-6 py-3.5">
                                @if ($package->imageUrl())
                                    <img src="{{ $package->imageUrl() }}" class="h-10 w-10 rounded-lg object-cover border border-slate-200 dark:border-slate-700">
                                @else
                                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6.75 3.75h3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-slate-900 dark:text-slate-100 font-medium">{{ $package->name }}</td>
                            <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400">{{ $package->items_count }} {{ __('produk') }}</td>
                            <td class="px-6 py-3.5 text-slate-900 dark:text-slate-100">Rp {{ number_format($package->price, 0, ',', '.') }}</td>
                            <td class="px-6 py-3.5">
                                @if ($package->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">{{ __('Aktif') }}</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ __('Nonaktif') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <x-icon-button wire:click="editPackage({{ $package->id }})" title="{{ __('Edit') }}" color="brand">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                    </x-icon-button>
                                    <x-icon-button wire:click="delete({{ $package->id }})" wire:confirm="Hapus paket ini?" title="{{ __('Hapus') }}" color="rose">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    </x-icon-button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">{{ __('Belum ada paket.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800">
            {{ $packages->links() }}
        </div>
    </div>

    <!-- Package Form Modal -->
    @if ($showFormModal)
    <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-slate-900/60" wire:click="$set('showFormModal', false)"></div>
        <div class="relative mb-6 bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-soft sm:max-w-lg sm:mx-auto">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $editingId ? __('Edit Paket') : __('Tambah Paket') }}</h3>

            <form wire:submit="save" class="mt-4 space-y-4">
                <div>
                    <x-input-label value="Foto Paket (opsional)" />
                    <div class="mt-1 flex items-center gap-4">
                        @if ($image)
                            <img src="{{ $image->temporaryUrl() }}" class="h-16 w-16 rounded-lg object-cover border border-slate-200 dark:border-slate-700">
                        @elseif ($existingImageUrl)
                            <img src="{{ $existingImageUrl }}" class="h-16 w-16 rounded-lg object-cover border border-slate-200 dark:border-slate-700">
                        @else
                            <span class="flex h-16 w-16 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </span>
                        @endif

                        <div class="flex-1">
                            <input wire:model="image" type="file" accept="image/*" class="block w-full text-sm text-slate-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-brand-500/10 dark:file:text-brand-300 dark:hover:file:bg-brand-500/20" />
                            @if ($existingImageUrl || $image)
                                <button type="button" wire:click="removeImage" class="mt-1 text-xs text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300">{{ __('Hapus foto') }}</button>
                            @endif
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('image')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="name" value="Nama Paket" />
                    <x-text-input wire:model="name" id="name" type="text" class="block w-full" placeholder="mis. Paket Hemat" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="description" value="Deskripsi (opsional)" />
                    <textarea wire:model="description" id="description" rows="2" class="mt-1 block w-full border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100"></textarea>
                </div>

                <div>
                    <x-input-label value="Produk dalam Paket" />
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ __('Stok tiap produk akan otomatis berkurang saat paket ini terjual.') }}</p>
                    <div class="mt-2 space-y-1.5 max-h-56 overflow-y-auto">
                        @forelse ($products as $product)
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-2 flex-1 cursor-pointer">
                                    <input type="checkbox" wire:click="toggleItem({{ $product->id }})" @checked(array_key_exists($product->id, $itemQty)) class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-600 dark:bg-slate-800">
                                    <span class="text-sm text-slate-700 dark:text-slate-200">{{ $product->name }}</span>
                                </label>
                                @if (array_key_exists($product->id, $itemQty))
                                    <x-text-input wire:model="itemQty.{{ $product->id }}" type="number" min="1" class="block w-20 text-sm" />
                                @endif
                            </div>
                        @empty
                            <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('Belum ada produk. Buat produk dulu di halaman Produk.') }}</span>
                        @endforelse
                    </div>
                    <x-input-error :messages="$errors->get('itemQty')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="price" value="Harga Jual Paket" />
                    <x-text-input wire:model="price" id="price" type="number" class="block w-full" />
                    <x-input-error :messages="$errors->get('price')" class="mt-2" />
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                    <input type="checkbox" wire:model="isActive" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-600 dark:bg-slate-800">
                    {{ __('Aktifkan paket ini di Kasir & Self Order') }}
                </label>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="$set('showFormModal', false)" class="px-4 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">{{ __('Batal') }}</button>
                    <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="save,image">
                        <span wire:loading.remove wire:target="save">{{ __('Simpan') }}</span>
                        <span wire:loading wire:target="save">{{ __('Menyimpan...') }}</span>
                    </x-primary-button>
                </div>
            </form>
        </div>
        </div>
    </div>
    @endif
</div>
