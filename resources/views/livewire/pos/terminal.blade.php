<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Product picker -->
    <div class="lg:col-span-2 space-y-4">
        <x-text-input wire:model.live.debounce.300ms="search" type="text" class="w-full" placeholder="Cari produk..." />

        @error('cart')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            @forelse ($products as $product)
                <button
                    type="button"
                    wire:click="addToCart({{ $product->id }})"
                    @disabled($product->stock_qty < 1)
                    class="text-left bg-white rounded-lg shadow-sm p-4 border border-transparent hover:border-indigo-400 disabled:opacity-40 disabled:cursor-not-allowed"
                >
                    <div class="font-medium text-gray-900">{{ $product->name }}</div>
                    <div class="text-sm text-gray-500 mt-1">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
                    <div class="text-xs {{ $product->isLowStock() ? 'text-red-500' : 'text-gray-400' }} mt-1">
                        Stok: {{ $product->stock_qty }} {{ $product->unit }}
                    </div>
                </button>
            @empty
                <p class="col-span-full text-sm text-gray-500">Produk tidak ditemukan.</p>
            @endforelse
        </div>
    </div>

    <!-- Cart / checkout -->
    <div class="bg-white rounded-lg shadow-sm p-4 flex flex-col h-fit">
        @if ($lastTransaction)
            <div class="text-center py-6 space-y-4">
                <div class="text-emerald-600 font-semibold text-lg">Transaksi Berhasil</div>
                <div class="text-sm text-gray-500">{{ $lastTransaction->transaction_no }}</div>
                <div class="text-2xl font-bold text-gray-900">Rp {{ number_format($lastTransaction->total, 0, ',', '.') }}</div>
                @if ($lastTransaction->payment_method === 'cash')
                    <div class="text-sm text-gray-500">Kembalian: Rp {{ number_format($lastTransaction->change_amount, 0, ',', '.') }}</div>
                @endif

                <div class="flex flex-col gap-2 pt-2">
                    <a
                        href="{{ route('transactions.receipt', $lastTransaction) }}"
                        target="_blank"
                        class="inline-flex justify-center items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                    >
                        Cetak Struk
                    </a>
                    <x-primary-button wire:click="newTransaction" class="justify-center">
                        Transaksi Baru
                    </x-primary-button>
                </div>
            </div>
        @else
            <h3 class="font-medium text-gray-900 mb-3">Keranjang</h3>

            <div class="flex-1 space-y-2 max-h-96 overflow-y-auto">
                @forelse ($cart as $productId => $item)
                    <div wire:key="cart-{{ $productId }}" class="flex items-center justify-between text-sm border-b border-gray-100 pb-2">
                        <div class="flex-1 pr-2">
                            <div class="text-gray-900">{{ $item['name'] }}</div>
                            <div class="text-gray-500">Rp {{ number_format($item['price'], 0, ',', '.') }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="decrementQty({{ $productId }})" class="w-6 h-6 rounded bg-gray-100 hover:bg-gray-200 text-gray-700">-</button>
                            <span class="w-6 text-center">{{ $item['qty'] }}</span>
                            <button type="button" wire:click="incrementQty({{ $productId }})" class="w-6 h-6 rounded bg-gray-100 hover:bg-gray-200 text-gray-700">+</button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Belum ada item.</p>
                @endforelse
            </div>

            <div class="mt-4 space-y-3 border-t border-gray-100 pt-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Subtotal</span>
                    <span class="text-gray-900">Rp {{ number_format($this->subtotal, 0, ',', '.') }}</span>
                </div>

                <div>
                    <x-input-label for="discount" value="Diskon (Rp)" />
                    <x-text-input wire:model.live="discount" id="discount" type="number" class="block w-full" />
                    <x-input-error :messages="$errors->get('discount')" class="mt-1" />
                </div>

                <div class="flex justify-between text-base font-semibold">
                    <span class="text-gray-900">Total</span>
                    <span class="text-gray-900">Rp {{ number_format($this->total, 0, ',', '.') }}</span>
                </div>

                <div>
                    <x-input-label for="paymentMethod" value="Metode Bayar" />
                    <select wire:model.live="paymentMethod" id="paymentMethod" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
                        <option value="cash">Tunai</option>
                        <option value="qris">QRIS</option>
                        <option value="kartu">Kartu</option>
                    </select>
                </div>

                @if ($paymentMethod === 'cash')
                    <div>
                        <x-input-label for="paidAmount" value="Uang Diterima" />
                        <x-text-input wire:model.live="paidAmount" id="paidAmount" type="number" class="block w-full" />
                        <x-input-error :messages="$errors->get('paidAmount')" class="mt-1" />
                    </div>

                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Kembalian</span>
                        <span class="text-gray-900">Rp {{ number_format($this->change, 0, ',', '.') }}</span>
                    </div>
                @endif

                <x-primary-button wire:click="checkout" class="w-full justify-center">
                    Bayar
                </x-primary-button>
            </div>
        @endif
    </div>
</div>
