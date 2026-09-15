<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-text-input wire:model.live.debounce.300ms="search" type="text" class="w-full sm:w-72" placeholder="Cari produk..." />
        <x-primary-button wire:click="createProduct">Tambah Produk</x-primary-button>
    </div>

    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="text-left text-gray-500">
                        <th class="px-6 py-3">Produk</th>
                        <th class="px-6 py-3">Kategori</th>
                        <th class="px-6 py-3">Harga Jual</th>
                        <th class="px-6 py-3">Stok</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($products as $product)
                        <tr wire:key="product-{{ $product->id }}">
                            <td class="px-6 py-3 text-gray-900">
                                {{ $product->name }}
                                @if ($product->sku)
                                    <div class="text-xs text-gray-400">SKU: {{ $product->sku }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-gray-500">{{ $product->category?->name ?? '-' }}</td>
                            <td class="px-6 py-3 text-gray-900">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                            <td class="px-6 py-3">
                                <span class="{{ $product->isLowStock() ? 'text-red-600 font-semibold' : 'text-gray-900' }}">
                                    {{ $product->stock_qty }} {{ $product->unit }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-right space-x-3 whitespace-nowrap">
                                <button wire:click="openStockModal({{ $product->id }})" class="text-emerald-600 hover:text-emerald-800">Stok</button>
                                <button wire:click="editProduct({{ $product->id }})" class="text-indigo-600 hover:text-indigo-800">Edit</button>
                                <button wire:click="delete({{ $product->id }})" wire:confirm="Hapus produk ini?" class="text-red-600 hover:text-red-800">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-6 text-center text-gray-500">Belum ada produk.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4">
            {{ $products->links() }}
        </div>
    </div>

    <!-- Product Form Modal -->
    @if ($showFormModal)
    <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-gray-500/75" wire:click="$set('showFormModal', false)"></div>
        <div class="relative mb-6 bg-white rounded-lg overflow-hidden shadow-xl sm:max-w-2xl sm:mx-auto">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900">{{ $editingId ? 'Edit Produk' : 'Tambah Produk' }}</h3>

            <form wire:submit="save" class="mt-4 space-y-4">
                <div>
                    <x-input-label for="name" value="Nama Produk" />
                    <x-text-input wire:model="name" id="name" type="text" class="block w-full" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="category_id" value="Kategori" />
                        <select wire:model="category_id" id="category_id" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
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
                    <button type="button" wire:click="$set('showFormModal', false)" class="text-sm text-gray-500 hover:text-gray-700">Batal</button>
                    <x-primary-button type="submit">Simpan</x-primary-button>
                </div>
            </form>
        </div>
        </div>
    </div>
    @endif

    <!-- Stock Adjustment Modal -->
    @if ($showStockModal)
    <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-gray-500/75" wire:click="$set('showStockModal', false)"></div>
        <div class="relative mb-6 bg-white rounded-lg overflow-hidden shadow-xl sm:max-w-md sm:mx-auto">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900">Atur Stok</h3>

            <form wire:submit="saveStock" class="mt-4 space-y-4">
                <div>
                    <x-input-label for="stockType" value="Jenis" />
                    <select wire:model="stockType" id="stockType" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
                        <option value="in">Stok Masuk</option>
                        <option value="out">Stok Keluar</option>
                        <option value="adjustment">Set Stok ke Jumlah Ini</option>
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
                    <button type="button" wire:click="$set('showStockModal', false)" class="text-sm text-gray-500 hover:text-gray-700">Batal</button>
                    <x-primary-button type="submit">Simpan</x-primary-button>
                </div>
            </form>
        </div>
        </div>
    </div>
    @endif
</div>
