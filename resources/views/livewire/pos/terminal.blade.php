<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Product picker -->
    <div class="lg:col-span-2 space-y-4">
        <div class="relative">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <x-text-input wire:model.live.debounce.300ms="search" type="text" class="w-full pl-10" placeholder="Cari produk..." />
        </div>

        @error('cart')
            <p class="text-sm text-rose-600">{{ $message }}</p>
        @enderror

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            @forelse ($products as $product)
                <button
                    type="button"
                    wire:click="addToCart({{ $product->id }})"
                    @disabled($product->stock_qty < 1)
                    class="text-left bg-white rounded-xl shadow-card p-4 border border-slate-200/70 hover:border-brand-300 hover:shadow-md disabled:opacity-40 disabled:cursor-not-allowed transition"
                >
                    <div class="font-medium text-slate-900">{{ $product->name }}</div>
                    <div class="text-sm text-brand-600 font-semibold mt-1">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
                    <div class="text-xs {{ $product->isLowStock() ? 'text-rose-500' : 'text-slate-400' }} mt-1">
                        Stok: {{ $product->stock_qty }} {{ $product->unit }}
                    </div>
                </button>
            @empty
                <p class="col-span-full text-sm text-slate-500">{{ __('Produk tidak ditemukan.') }}</p>
            @endforelse
        </div>
    </div>

    <!-- Cart / checkout -->
    <div class="bg-white rounded-2xl shadow-card border border-slate-200/70 p-5 flex flex-col h-fit lg:sticky lg:top-24">
        @if ($lastTransaction)
            <div class="text-center py-6 space-y-4">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                </span>
                <div class="text-emerald-600 font-semibold text-lg">{{ __('Transaksi Berhasil') }}</div>
                <div class="text-sm text-slate-500">{{ $lastTransaction->transaction_no }}</div>
                <div class="text-2xl font-bold text-slate-900">Rp {{ number_format($lastTransaction->total, 0, ',', '.') }}</div>
                @if ($lastTransaction->payment_method === 'cash')
                    <div class="text-sm text-slate-500">{{ __('Kembalian') }}: Rp {{ number_format($lastTransaction->change_amount, 0, ',', '.') }}</div>
                @endif

                <div class="flex flex-col gap-2 pt-2">
                    <a
                        href="{{ route('transactions.receipt', $lastTransaction) }}"
                        target="_blank"
                        class="inline-flex justify-center items-center px-4 py-2.5 bg-white border border-slate-200 rounded-lg font-medium text-sm text-slate-700 hover:bg-slate-50 transition"
                    >
                        {{ __('Cetak Struk') }}
                    </a>
                    <x-primary-button wire:click="newTransaction" class="justify-center">
                        {{ __('Transaksi Baru') }}
                    </x-primary-button>
                </div>
            </div>
        @else
            <h3 class="font-semibold text-slate-900 mb-3">{{ __('Keranjang') }}</h3>

            <div class="flex-1 space-y-2 max-h-96 overflow-y-auto">
                @forelse ($cart as $productId => $item)
                    <div wire:key="cart-{{ $productId }}" class="flex items-center justify-between text-sm border-b border-slate-100 pb-2.5">
                        <div class="flex-1 pr-2">
                            <div class="text-slate-900">{{ $item['name'] }}</div>
                            <div class="text-slate-500">Rp {{ number_format($item['price'], 0, ',', '.') }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="decrementQty({{ $productId }})" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium">-</button>
                            <span class="w-6 text-center font-medium text-slate-900">{{ $item['qty'] }}</span>
                            <button type="button" wire:click="incrementQty({{ $productId }})" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium">+</button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400 py-6 text-center">{{ __('Belum ada item.') }}</p>
                @endforelse
            </div>

            <div class="mt-4 space-y-3 border-t border-slate-100 pt-4">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">{{ __('Subtotal') }}</span>
                    <span class="text-slate-900">Rp {{ number_format($this->subtotal, 0, ',', '.') }}</span>
                </div>

                <div>
                    <x-input-label for="discount" value="Diskon (Rp)" />
                    <x-text-input wire:model.live="discount" id="discount" type="number" class="block w-full" />
                    <x-input-error :messages="$errors->get('discount')" class="mt-1" />
                </div>

                <div class="flex justify-between text-base font-semibold pt-1">
                    <span class="text-slate-900">{{ __('Total') }}</span>
                    <span class="text-brand-600">Rp {{ number_format($this->total, 0, ',', '.') }}</span>
                </div>

                <div>
                    <x-input-label for="paymentMethod" value="Metode Bayar" />
                    <select wire:model.live="paymentMethod" id="paymentMethod" class="block w-full mt-1 border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900">
                        <option value="cash">{{ __('Tunai') }}</option>
                        <option value="qris">QRIS</option>
                        <option value="kartu">{{ __('Kartu') }}</option>
                    </select>
                </div>

                @if ($paymentMethod === 'cash')
                    <div>
                        <x-input-label for="paidAmount" value="Uang Diterima" />
                        <x-text-input wire:model.live="paidAmount" id="paidAmount" type="number" class="block w-full" />
                        <x-input-error :messages="$errors->get('paidAmount')" class="mt-1" />
                    </div>

                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">{{ __('Kembalian') }}</span>
                        <span class="text-slate-900 font-medium">Rp {{ number_format($this->change, 0, ',', '.') }}</span>
                    </div>
                @endif

                <x-primary-button wire:click="checkout" class="w-full justify-center py-3">
                    {{ __('Bayar') }}
                </x-primary-button>
            </div>
        @endif
    </div>
</div>
