<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="relative w-full sm:w-72">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <x-text-input wire:model.live.debounce.300ms="search" type="text" class="w-full pl-10" placeholder="Cari promo..." />
        </div>
        <x-primary-button wire:click="createPromo">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('Tambah Promo') }}
        </x-primary-button>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500 bg-slate-50/70 dark:bg-slate-800/40">
                        <x-th-sort field="name" label="{{ __('Promo') }}" :sortField="$sortField" :sortDirection="$sortDirection" />
                        <th class="px-6 py-3">{{ __('Produk') }}</th>
                        <th class="px-6 py-3">{{ __('Tipe & Aturan') }}</th>
                        <th class="px-6 py-3">{{ __('Periode') }}</th>
                        <th class="px-6 py-3">{{ __('Status') }}</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($promos as $promo)
                        <tr wire:key="promo-{{ $promo->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="px-6 py-3.5 text-slate-900 dark:text-slate-100 font-medium">{{ $promo->name }}</td>
                            <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400">{{ $promo->product?->name ?? '-' }}</td>
                            <td class="px-6 py-3.5">
                                @if ($promo->isDiscount())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">
                                        {{ $promo->discount_type === 'percent' ? '-'.$promo->discount_value.'%' : '-Rp'.number_format($promo->discount_value, 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                        {{ __('Beli :min gratis :qty :name', ['min' => $promo->min_qty, 'qty' => $promo->gift_qty, 'name' => $promo->giftProduct?->name ?? '-']) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                @if ($promo->starts_at || $promo->ends_at)
                                    {{ $promo->starts_at?->translatedFormat('d M Y') ?? '...' }} - {{ $promo->ends_at?->translatedFormat('d M Y') ?? '...' }}
                                @else
                                    <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('Tanpa batas waktu') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5">
                                @if ($promo->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">{{ __('Aktif') }}</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ __('Nonaktif') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <x-icon-button wire:click="editPromo({{ $promo->id }})" title="{{ __('Edit') }}" color="brand">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                    </x-icon-button>
                                    <x-icon-button wire:click="delete({{ $promo->id }})" wire:confirm="Hapus promo ini?" title="{{ __('Hapus') }}" color="rose">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    </x-icon-button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">{{ __('Belum ada promo.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800">
            {{ $promos->links() }}
        </div>
    </div>

    <!-- Promo Form Modal -->
    @if ($showFormModal)
    <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-slate-900/60" wire:click="$set('showFormModal', false)"></div>
        <div class="relative mb-6 bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-soft sm:max-w-md sm:mx-auto">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $editingId ? __('Edit Promo') : __('Tambah Promo') }}</h3>

            <form wire:submit="save" class="mt-4 space-y-4">
                <div>
                    <x-input-label for="name" value="Nama Promo" />
                    <x-text-input wire:model="name" id="name" type="text" class="block w-full" placeholder="mis. Promo Akhir Pekan" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label value="Tipe Promo" />
                    <div class="mt-1.5 grid grid-cols-2 gap-2">
                        <label class="flex items-center justify-center gap-2 px-3 py-2.5 rounded-lg border cursor-pointer text-sm font-medium transition {{ $type === 'discount' ? 'bg-brand-50 border-brand-300 text-brand-700 dark:bg-brand-500/10 dark:border-brand-700 dark:text-brand-300' : 'bg-slate-50 border-slate-200 text-slate-500 dark:bg-slate-800/60 dark:border-slate-700 dark:text-slate-400' }}">
                            <input type="radio" wire:model.live="type" value="discount" class="sr-only">
                            {{ __('Diskon Harga') }}
                        </label>
                        <label class="flex items-center justify-center gap-2 px-3 py-2.5 rounded-lg border cursor-pointer text-sm font-medium transition {{ $type === 'gift' ? 'bg-brand-50 border-brand-300 text-brand-700 dark:bg-brand-500/10 dark:border-brand-700 dark:text-brand-300' : 'bg-slate-50 border-slate-200 text-slate-500 dark:bg-slate-800/60 dark:border-slate-700 dark:text-slate-400' }}">
                            <input type="radio" wire:model.live="type" value="gift" class="sr-only">
                            {{ __('Hadiah Gratis') }}
                        </label>
                    </div>
                </div>

                <div>
                    <x-input-label for="productId" value="Produk yang Dipromokan" />
                    <select wire:model="productId" id="productId" class="block w-full mt-1 border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100">
                        <option value="">- Pilih produk -</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('productId')" class="mt-2" />
                </div>

                @if ($type === 'discount')
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="discountType" value="Tipe Diskon" />
                            <select wire:model="discountType" id="discountType" class="block w-full mt-1 border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100">
                                <option value="percent">{{ __('Persen (%)') }}</option>
                                <option value="fixed">{{ __('Nominal (Rp)') }}</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="discountValue" value="Nilai Diskon" />
                            <x-text-input wire:model="discountValue" id="discountValue" type="number" class="block w-full" />
                            <x-input-error :messages="$errors->get('discountValue')" class="mt-2" />
                        </div>
                    </div>
                @else
                    <div>
                        <x-input-label for="minQty" value="Minimal Beli (qty produk di atas)" />
                        <x-text-input wire:model="minQty" id="minQty" type="number" min="1" class="block w-full" />
                        <x-input-error :messages="$errors->get('minQty')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="giftProductId" value="Produk Hadiah" />
                        <select wire:model="giftProductId" id="giftProductId" class="block w-full mt-1 border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100">
                            <option value="">- Pilih produk hadiah -</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('giftProductId')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="giftQty" value="Jumlah Hadiah" />
                        <x-text-input wire:model="giftQty" id="giftQty" type="number" min="1" class="block w-full" />
                        <x-input-error :messages="$errors->get('giftQty')" class="mt-2" />
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="startsAt" value="Mulai (opsional)" />
                        <input wire:model="startsAt" id="startsAt" type="date" class="mt-1 block w-full border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100">
                    </div>
                    <div>
                        <x-input-label for="endsAt" value="Berakhir (opsional)" />
                        <input wire:model="endsAt" id="endsAt" type="date" class="mt-1 block w-full border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100">
                        <x-input-error :messages="$errors->get('endsAt')" class="mt-2" />
                    </div>
                </div>
                <p class="text-xs text-slate-400 dark:text-slate-500 -mt-2">{{ __('Kosongkan untuk berlaku tanpa batas waktu.') }}</p>

                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                    <input type="checkbox" wire:model="isActive" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-600 dark:bg-slate-800">
                    {{ __('Aktifkan promo ini') }}
                </label>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="$set('showFormModal', false)" class="px-4 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">{{ __('Batal') }}</button>
                    <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="save">
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
