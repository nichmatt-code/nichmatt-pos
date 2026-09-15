<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="relative w-full sm:w-72">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <x-text-input wire:model.live.debounce.300ms="search" type="text" class="w-full pl-10" placeholder="Cari produk..." />
        </div>
        <x-primary-button wire:click="createProduct">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('Tambah Produk') }}
        </x-primary-button>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500 bg-slate-50/70 dark:bg-slate-800/40">
                        <th class="px-6 py-3"></th>
                        <th class="px-6 py-3">{{ __('Produk') }}</th>
                        <th class="px-6 py-3">{{ __('Kategori') }}</th>
                        <th class="px-6 py-3">{{ __('Tag') }}</th>
                        <th class="px-6 py-3">{{ __('Harga Jual') }}</th>
                        <th class="px-6 py-3">{{ __('Stok') }}</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($products as $product)
                        <tr wire:key="product-{{ $product->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="pl-6 py-3.5">
                                <x-product-thumb :product="$product" class="h-10 w-10 rounded-lg border border-slate-200 dark:border-slate-700" />
                            </td>
                            <td class="px-6 py-3.5 text-slate-900 dark:text-slate-100 font-medium">
                                {{ $product->name }}
                                @if ($product->sku)
                                    <div class="text-xs font-normal text-slate-400 dark:text-slate-500">SKU: {{ $product->sku }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400">{{ $product->category?->name ?? '-' }}</td>
                            <td class="px-6 py-3.5">
                                @forelse ($product->tags as $tag)
                                    <span class="inline-flex items-center px-2 py-0.5 mr-1 mb-1 rounded-full text-xs font-medium bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">{{ $tag->name }}</span>
                                @empty
                                    <span class="text-slate-400 dark:text-slate-500">-</span>
                                @endforelse
                            </td>
                            <td class="px-6 py-3.5 text-slate-900 dark:text-slate-100">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                            <td class="px-6 py-3.5">
                                @if ($product->is_out_of_stock)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400">
                                        {{ __('Habis') }}
                                    </span>
                                @elseif ($product->is_unlimited_stock)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-brand-50 dark:bg-brand-500/10 text-brand-700 dark:text-brand-300">
                                        &infin; {{ $product->unit }}
                                    </span>
                                @elseif ($product->isLowStock())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400">
                                        {{ $product->stock_qty }} {{ $product->unit }}
                                    </span>
                                @else
                                    <span class="text-slate-900 dark:text-slate-100">{{ $product->stock_qty }} {{ $product->unit }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-right space-x-3 whitespace-nowrap">
                                @if ($product->barcode)
                                    <a href="{{ route('products.label', $product) }}" target="_blank" class="text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 font-medium">{{ __('Label') }}</a>
                                @endif
                                <button wire:click="toggleOutOfStock({{ $product->id }})" class="{{ $product->is_out_of_stock ? 'text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300' : 'text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300' }} font-medium">
                                    {{ $product->is_out_of_stock ? __('Tersedia Lagi') : __('Tandai Habis') }}
                                </button>
                                <button wire:click="openStockModal({{ $product->id }})" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300 font-medium">{{ __('Stok') }}</button>
                                <button wire:click="editProduct({{ $product->id }})" class="text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300 font-medium">{{ __('Edit') }}</button>
                                <button wire:click="delete({{ $product->id }})" wire:confirm="Hapus produk ini?" class="text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300 font-medium">{{ __('Hapus') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">{{ __('Belum ada produk.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800">
            {{ $products->links() }}
        </div>
    </div>

    <!-- Product Form Modal -->
    @if ($showFormModal)
    <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-slate-900/60" wire:click="$set('showFormModal', false)"></div>
        <div class="relative mb-6 bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-soft sm:max-w-2xl sm:mx-auto">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $editingId ? __('Edit Produk') : __('Tambah Produk') }}</h3>

            <form wire:submit="save" class="mt-4 space-y-4">
                <div>
                    <x-input-label value="Foto Produk" />
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
                            <div wire:loading wire:target="image" class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ __('Mengunggah...') }}</div>
                            @if ($existingImageUrl || $image)
                                <button type="button" wire:click="removeImage" class="mt-1 text-xs text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300">{{ __('Hapus foto') }}</button>
                            @endif
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('image')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="name" value="Nama Produk" />
                    <x-text-input wire:model="name" id="name" type="text" class="block w-full" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="category_id" value="Kategori" />
                        <select wire:model="category_id" id="category_id" class="block w-full mt-1 border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100">
                            <option value="">- Tanpa kategori -</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="unit" value="Satuan" />
                        <x-text-input wire:model="unit" id="unit" type="text" class="block w-full" placeholder="pcs, kg, dus" />
                    </div>
                </div>

                <div>
                    <x-input-label value="Tag (bisa lebih dari satu)" />
                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                        @forelse ($tags as $tag)
                            <label class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border cursor-pointer transition {{ in_array($tag->id, $tag_ids) ? 'bg-brand-50 border-brand-300 text-brand-700 dark:bg-brand-500/10 dark:border-brand-700 dark:text-brand-300' : 'bg-slate-50 border-slate-200 text-slate-500 dark:bg-slate-800/60 dark:border-slate-700 dark:text-slate-400' }}">
                                <input type="checkbox" wire:model="tag_ids" value="{{ $tag->id }}" class="sr-only">
                                {{ $tag->name }}
                            </label>
                        @empty
                            <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('Belum ada tag.') }}</span>
                        @endforelse
                    </div>
                    <div class="mt-2 flex gap-2">
                        <x-text-input wire:model="newTagName" wire:keydown.enter.prevent="addTag" type="text" class="block w-full text-sm" placeholder="Tambah tag baru, mis. Pedas" />
                        <button type="button" wire:click="addTag" class="shrink-0 px-3 py-2 text-xs font-medium text-brand-700 bg-brand-50 rounded-lg hover:bg-brand-100 dark:text-brand-300 dark:bg-brand-500/10 dark:hover:bg-brand-500/20">{{ __('Tambah') }}</button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="sku" value="SKU (opsional)" />
                        <div class="mt-1 flex gap-2">
                            <x-text-input wire:model="sku" id="sku" type="text" class="block w-full" placeholder="Tulis atau generate" />
                            <button type="button" wire:click="generateSku" class="shrink-0 px-3 py-2 text-xs font-medium text-brand-700 bg-brand-50 rounded-lg hover:bg-brand-100 dark:text-brand-300 dark:bg-brand-500/10 dark:hover:bg-brand-500/20">{{ __('Generate') }}</button>
                        </div>
                        <x-input-error :messages="$errors->get('sku')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="barcode" value="Barcode (opsional)" />
                        <div class="mt-1 flex gap-2">
                            <x-text-input wire:model="barcode" id="barcode" type="text" class="block w-full" placeholder="Scan atau generate" />
                            <button type="button" wire:click="generateBarcode" class="shrink-0 px-3 py-2 text-xs font-medium text-brand-700 bg-brand-50 rounded-lg hover:bg-brand-100 dark:text-brand-300 dark:bg-brand-500/10 dark:hover:bg-brand-500/20">{{ __('Generate') }}</button>
                        </div>
                        <x-input-error :messages="$errors->get('barcode')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="cost_price" value="Harga Modal" />
                        <x-text-input wire:model="cost_price" id="cost_price" type="number" class="block w-full" />
                        <x-input-error :messages="$errors->get('cost_price')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="price" value="Harga Jual" />
                        <x-text-input wire:model="price" id="price" type="number" class="block w-full" />
                        <x-input-error :messages="$errors->get('price')" class="mt-2" />
                    </div>
                </div>

                <div class="flex flex-wrap gap-4">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                        <input type="checkbox" wire:model.live="is_unlimited_stock" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-600 dark:bg-slate-800">
                        {{ __('Stok tak terbatas (infinity)') }}
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                        <input type="checkbox" wire:model="is_out_of_stock" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 dark:border-slate-600 dark:bg-slate-800">
                        {{ __('Tandai habis sekarang') }}
                    </label>
                </div>

                @unless ($editingId || $is_unlimited_stock)
                    <div>
                        <x-input-label for="stock_qty" value="Stok Awal" />
                        <x-text-input wire:model="stock_qty" id="stock_qty" type="number" class="block w-full" />
                        <x-input-error :messages="$errors->get('stock_qty')" class="mt-2" />
                    </div>
                @endunless

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
