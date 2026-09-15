<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="relative w-full sm:w-72">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <x-text-input wire:model.live.debounce.300ms="search" type="text" class="w-full pl-10" placeholder="Cari produk..." />
        </div>
        <x-primary-button wire:click="createProduct">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('Tambah Produk') }}
        </x-primary-button>
    </div>

    <div class="bg-white border border-slate-200/70 shadow-card rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 bg-slate-50/70">
                        <th class="px-6 py-3">{{ __('Produk') }}</th>
                        <th class="px-6 py-3">{{ __('Kategori') }}</th>
                        <th class="px-6 py-3">{{ __('Harga Jual') }}</th>
                        <th class="px-6 py-3">{{ __('Stok') }}</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($products as $product)
                        <tr wire:key="product-{{ $product->id }}" class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-3.5 text-slate-900 font-medium">
                                {{ $product->name }}
                                @if ($product->sku)
                                    <div class="text-xs font-normal text-slate-400">SKU: {{ $product->sku }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-slate-500">{{ $product->category?->name ?? '-' }}</td>
                            <td class="px-6 py-3.5 text-slate-900">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                            <td class="px-6 py-3.5">
                                @if ($product->isLowStock())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-600">
                                        {{ $product->stock_qty }} {{ $product->unit }}
                                    </span>
                                @else
                                    <span class="text-slate-900">{{ $product->stock_qty }} {{ $product->unit }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-right space-x-3 whitespace-nowrap">
                                <button wire:click="openStockModal({{ $product->id }})" class="text-emerald-600 hover:text-emerald-800 font-medium">{{ __('Stok') }}</button>
                                <button wire:click="editProduct({{ $product->id }})" class="text-brand-600 hover:text-brand-800 font-medium">{{ __('Edit') }}</button>
                                <button wire:click="delete({{ $product->id }})" wire:confirm="Hapus produk ini?" class="text-rose-600 hover:text-rose-800 font-medium">{{ __('Hapus') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-400">{{ __('Belum ada produk.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-100">
            {{ $products->links() }}
        </div>
    </div>

    <!-- Product Form Modal -->
    @if ($showFormModal)
    <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-slate-900/60" wire:click="$set('showFormModal', false)"></div>
        <div class="relative mb-6 bg-white rounded-2xl overflow-hidden shadow-soft sm:max-w-2xl sm:mx-auto">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-slate-900">{{ $editingId ? __('Edit Produk') : __('Tambah Produk') }}</h3>

            <form wire:submit="save" class="mt-4 space-y-4">
                <div>
                    <x-input-label for="name" value="Nama Produk" />
                    <x-text-input wire:model="name" id="name" type="text" class="block w-full" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="category_id" value="Kategori" />
                        <select wire:model="category_id" id="category_id" class="block w-full mt-1 border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900">
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

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="sku" value="SKU (opsional)" />
                        <x-text-input wire:model="sku" id="sku" type="text" class="block w-full" />
                    </div>
                    <div>
                        <x-input-label for="barcode" value="Barcode (opsional)" />
                        <x-text-input wire:model="barcode" id="barcode" type="text" class="block w-full" />
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

                @unless ($editingId)
                    <div>
                        <x-input-label for="stock_qty" value="Stok Awal" />
                        <x-text-input wire:model="stock_qty" id="stock_qty" type="number" class="block w-full" />
                        <x-input-error :messages="$errors->get('stock_qty')" class="mt-2" />
                    </div>
                @endunless

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="$set('showFormModal', false)" class="px-4 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-800">{{ __('Batal') }}</button>
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
        <div class="relative mb-6 bg-white rounded-2xl overflow-hidden shadow-soft sm:max-w-md sm:mx-auto">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-slate-900">{{ __('Atur Stok') }}</h3>

            <form wire:submit="saveStock" class="mt-4 space-y-4">
                <div>
                    <x-input-label for="stockType" value="Jenis" />
                    <select wire:model="stockType" id="stockType" class="block w-full mt-1 border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900">
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
                    <button type="button" wire:click="$set('showStockModal', false)" class="px-4 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-800">{{ __('Batal') }}</button>
                    <x-primary-button type="submit">{{ __('Simpan') }}</x-primary-button>
                </div>
            </form>
        </div>
        </div>
    </div>
    @endif
</div>
