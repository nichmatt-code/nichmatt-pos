<div x-data x-on:bill-ready.window="window.open('{{ route('pos.bill') }}', '_blank')"
    class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Product picker -->
    <div class="lg:col-span-2 space-y-4">
        <div class="relative">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-brand-500 dark:text-brand-400"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4 6h.01M4 12h.01M4 18h.01M8 6h1m-1 6h1m-1 6h1m4-18v18m4-18v6m0 6v6" />
            </svg>
            <input wire:model="barcodeInput" wire:keydown.enter.prevent="scanBarcode" type="text" autofocus
                placeholder="Scan barcode produk..."
                class="w-full pl-10 border-slate-200 focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 placeholder:text-slate-400 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-100 dark:placeholder:text-slate-500" />
        </div>
        @error('barcodeInput')
            <p class="text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
        @enderror

        <div class="relative">
            <svg wire:loading.remove wire:target="search"
                class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 dark:text-slate-500"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <svg wire:loading wire:target="search"
                class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-brand-500 dark:text-brand-400 animate-spin"
                fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <x-text-input wire:model.live.debounce.300ms="search" type="text" class="w-full pl-10"
                placeholder="Cari produk..." />
        </div>

        @if ($categories->isNotEmpty())
            <div class="flex flex-wrap gap-1.5">
                <button type="button" wire:click="selectCategory(null)"
                    class="px-3 py-1.5 rounded-full text-xs font-semibold border transition {{ is_null($activeCategoryId) ? 'bg-slate-900 border-slate-900 text-white dark:bg-slate-100 dark:border-slate-100 dark:text-slate-900' : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300 dark:bg-slate-800/60 dark:border-slate-700 dark:text-slate-400' }}">
                    {{ __('Semua') }}
                </button>
                @foreach ($categories as $category)
                    <button type="button" wire:click="selectCategory({{ $category->id }})"
                        class="px-3 py-1.5 rounded-full text-xs font-semibold border transition {{ $activeCategoryId === $category->id ? 'bg-slate-900 border-slate-900 text-white dark:bg-slate-100 dark:border-slate-100 dark:text-slate-900' : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300 dark:bg-slate-800/60 dark:border-slate-700 dark:text-slate-400' }}">
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>
        @endif

        @if ($tags->isNotEmpty())
            <div class="flex flex-wrap gap-1.5">
                @foreach ($tags as $tag)
                    <button type="button" wire:click="toggleTag({{ $tag->id }})"
                        class="px-2.5 py-1 rounded-full text-xs font-medium border transition {{ in_array($tag->id, $activeTags) ? 'bg-brand-600 border-brand-600 text-white dark:bg-brand-500 dark:border-brand-500' : 'bg-white border-slate-200 text-slate-500 hover:border-brand-300 dark:bg-slate-800/60 dark:border-slate-700 dark:text-slate-400' }}">
                        {{ $tag->name }}
                    </button>
                @endforeach
            </div>
        @endif

        @error('cart')
            <p class="text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
        @enderror

        <div wire:loading.class="opacity-40" wire:target="search,selectCategory,toggleTag"
            class="transition-opacity duration-150">
        @if ($productGroups)
            <div class="space-y-5">
                @foreach ($categories as $category)
                    @php $items = $productGroups->get($category->id); @endphp
                    @if ($items && $items->isNotEmpty())
                        <div>
                            <h4
                                class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-2">
                                {{ $category->name }}</h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                @foreach ($items as $product)
                                    @include('livewire.pos.product-card', [
                                        'product' => $product,
                                        'showImage' => $this->store->show_product_images,
                                    ])
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach

                @php $uncategorized = $productGroups->get(0); @endphp
                @if ($uncategorized && $uncategorized->isNotEmpty())
                    <div>
                        <h4
                            class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-2">
                            {{ __('Tanpa Kategori') }}</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach ($uncategorized as $product)
                                @include('livewire.pos.product-card', [
                                    'product' => $product,
                                    'showImage' => $this->store->show_product_images,
                                ])
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($products->isEmpty())
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Produk tidak ditemukan.') }}</p>
                @endif
            </div>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @forelse ($products as $product)
                    @include('livewire.pos.product-card', [
                        'product' => $product,
                        'showImage' => $this->store->show_product_images,
                    ])
                @empty
                    <p class="col-span-full text-sm text-slate-500 dark:text-slate-400">
                        {{ __('Produk tidak ditemukan.') }}</p>
                @endforelse
            </div>
        @endif
        </div>
    </div>

    <!-- Cart / checkout -->
    <div
        class="bg-white dark:bg-slate-900 rounded-2xl shadow-card border border-slate-200/70 dark:border-slate-800 p-5 flex flex-col h-fit lg:sticky lg:top-24">
        @if ($lastTransaction)
            <div wire:transition class="text-center py-6 space-y-4">
                <span
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
                <div class="text-emerald-600 dark:text-emerald-400 font-semibold text-lg">
                    {{ __('Transaksi Berhasil') }}</div>
                <div class="text-sm text-slate-500 dark:text-slate-400">{{ $lastTransaction->transaction_no }}</div>
                @if ($lastTransaction->customer_name)
                    <div class="text-sm text-slate-500 dark:text-slate-400">{{ __('Customer') }}:
                        {{ $lastTransaction->customer_name }}</div>
                @endif
                <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">Rp
                    {{ number_format($lastTransaction->total, 0, ',', '.') }}</div>
                @if ($lastTransaction->payment_method === 'cash')
                    <div class="text-sm text-slate-500 dark:text-slate-400">{{ __('Kembalian') }}: Rp
                        {{ number_format($lastTransaction->change_amount, 0, ',', '.') }}</div>
                @endif

                <div class="flex flex-col gap-2 pt-2">
                    <a href="{{ route('transactions.receipt', $lastTransaction) }}" target="_blank"
                        class="inline-flex justify-center items-center px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg font-medium text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                        {{ __('Cetak Struk') }}
                    </a>
                    <x-primary-button wire:click="newTransaction" class="justify-center">
                        {{ __('Transaksi Baru') }}
                    </x-primary-button>
                </div>
            </div>
        @else
            <div
                class="mb-4 rounded-xl border border-brand-100 dark:border-brand-500/20 bg-brand-50/60 dark:bg-brand-500/10 p-3">
                <x-input-label for="orderCodeInput" value="Input Kode Self Order (Untuk Pembayaran)"
                    class="text-brand-700 dark:text-brand-300" />
                <div class="mt-1.5 flex gap-2">
                    <input wire:model="orderCodeInput" wire:keydown.enter.prevent="claimCode" id="orderCodeInput"
                        type="text" maxlength="6" placeholder="mis. A3F9K2"
                        class="block w-full uppercase tracking-widest border-slate-200 focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 placeholder:text-slate-400 placeholder:tracking-normal dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-100 dark:placeholder:text-slate-500" />
                    <x-secondary-button type="button" wire:click="claimCode" wire:loading.attr="disabled"
                        wire:target="claimCode" class="shrink-0">
                        <span wire:loading.remove wire:target="claimCode">{{ __('Ambil') }}</span>
                        <span wire:loading wire:target="claimCode">{{ __('Mencari...') }}</span>
                    </x-secondary-button>
                </div>
                <x-input-error :messages="$errors->get('orderCodeInput')" class="mt-1.5" />
            </div>

            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Keranjang') }}</h3>
                @if ($claimedSelfOrderId)
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-brand-50 dark:bg-brand-500/10 text-brand-700 dark:text-brand-300">{{ __('Dari Self Order') }}</span>
                @endif
            </div>

            <div class="space-y-3 mb-3">
                <div class="relative" x-data="{ open: false }">
                    <x-input-label for="customerName" value="Customer (opsional)" />

                    @if ($selectedCustomerId)
                        <div
                            class="mt-1 flex items-center justify-between px-3 py-2 rounded-lg bg-brand-50 dark:bg-brand-500/10 border border-brand-100 dark:border-brand-900/60">
                            <span
                                class="text-sm font-medium text-brand-700 dark:text-brand-300">{{ $customerName }}</span>
                            <button type="button" wire:click="clearSelectedCustomer"
                                class="text-xs text-brand-600 dark:text-brand-400 underline">{{ __('Ganti') }}</button>
                        </div>
                    @else
                        <x-text-input wire:model.live.debounce.300ms="customerName" id="customerName" type="text"
                            class="block w-full" placeholder="Cari member atau ketik nama baru"
                            x-on:focus="open = true" x-on:blur="setTimeout(() => open = false, 150)" />
                        @if ($this->customerMatches->isNotEmpty())
                            <div x-show="open"
                                class="absolute z-10 mt-1 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg overflow-hidden">
                                @foreach ($this->customerMatches as $match)
                                    <button type="button" wire:click="selectCustomer({{ $match->id }})"
                                        class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                                        <div class="font-medium text-slate-900 dark:text-slate-100">
                                            {{ $match->name }}</div>
                                        <div class="text-xs text-slate-400 dark:text-slate-500">{{ $match->phone }}
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>
                <div>
                    <x-input-label for="orderNote" value="Catatan Order (opsional)" />
                    <x-text-input wire:model.blur="orderNote" id="orderNote" type="text" class="block w-full"
                        placeholder="mis. dibungkus terpisah" />
                </div>
            </div>

            <div class="flex-1 space-y-2 max-h-96 overflow-y-auto">
                @forelse ($cart as $productId => $item)
                    <div wire:key="cart-{{ $productId }}" wire:transition
                        class="border-b border-slate-100 dark:border-slate-800 pb-2.5">
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex-1 pr-2">
                                <div class="text-slate-900 dark:text-slate-100">{{ $item['name'] }}</div>
                                @if ($this->store->allow_price_edit)
                                    <div class="flex items-center gap-1 mt-0.5">
                                        <span class="text-xs text-slate-400 dark:text-slate-500">Rp</span>
                                        <input type="number" min="0"
                                            wire:model.blur="cart.{{ $productId }}.price"
                                            class="w-24 text-xs border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg py-1 px-2 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100" />
                                    </div>
                                @else
                                    <div class="text-slate-500 dark:text-slate-400">Rp
                                        {{ number_format($item['price'], 0, ',', '.') }}</div>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="decrementQty({{ $productId }})"
                                    class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-medium">-</button>
                                <span
                                    class="w-6 text-center font-medium text-slate-900 dark:text-slate-100">{{ $item['qty'] }}</span>
                                <button type="button" wire:click="incrementQty({{ $productId }})"
                                    class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-medium">+</button>
                            </div>
                        </div>
                        <input type="text" wire:model.blur="cart.{{ $productId }}.note"
                            placeholder="Catatan (opsional), mis. tanpa gula"
                            class="mt-1.5 w-full text-xs border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg placeholder:text-slate-400 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500" />
                    </div>
                @empty
                    <p class="text-sm text-slate-400 dark:text-slate-500 py-6 text-center">{{ __('Belum ada item.') }}
                    </p>
                @endforelse
            </div>

            <div class="mt-4 space-y-3 border-t border-slate-100 dark:border-slate-800 pt-4">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500 dark:text-slate-400">{{ __('Subtotal') }}</span>
                    <span class="text-slate-900 dark:text-slate-100">Rp
                        {{ number_format($this->subtotal, 0, ',', '.') }}</span>
                </div>

                <div>
                    <x-input-label for="discount" value="Diskon (Rp)" />
                    <x-text-input wire:model.live="discount" id="discount" type="number" class="block w-full" />
                    <x-input-error :messages="$errors->get('discount')" class="mt-1" />
                </div>

                @if ($this->store->service_charge_percent > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500 dark:text-slate-400">{{ __('Service Charge') }}
                            ({{ $this->store->service_charge_percent }}%)</span>
                        <span class="text-slate-900 dark:text-slate-100">Rp
                            {{ number_format($this->serviceChargeAmount, 0, ',', '.') }}</span>
                    </div>
                @endif

                @if ($this->store->tax_percent > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500 dark:text-slate-400">{{ __('Pajak') }}
                            ({{ $this->store->tax_percent }}%)</span>
                        <span class="text-slate-900 dark:text-slate-100">Rp
                            {{ number_format($this->taxAmount, 0, ',', '.') }}</span>
                    </div>
                @endif

                <div class="flex justify-between text-base font-semibold pt-1">
                    <span class="text-slate-900 dark:text-slate-100">{{ __('Total') }}</span>
                    <span class="text-brand-600 dark:text-brand-400">Rp
                        {{ number_format($this->total, 0, ',', '.') }}</span>
                </div>

                <div>
                    <x-input-label for="paymentMethod" value="Metode Bayar" />
                    <select wire:model.live="paymentMethod" id="paymentMethod"
                        class="block w-full mt-1 border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100">
                        <option value="cash">{{ __('Tunai') }}</option>
                        <option value="qris">QRIS</option>
                        <option value="kartu">{{ __('Kartu') }}</option>
                    </select>
                </div>

                @if ($paymentMethod === 'cash')
                    <div>
                        <x-input-label for="paidAmount" value="Uang Diterima" />
                        <x-text-input wire:model.live="paidAmount" id="paidAmount" type="number"
                            class="block w-full" />
                        <x-input-error :messages="$errors->get('paidAmount')" class="mt-1" />
                    </div>

                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500 dark:text-slate-400">{{ __('Kembalian') }}</span>
                        <span class="text-slate-900 dark:text-slate-100 font-medium">Rp
                            {{ number_format($this->change, 0, ',', '.') }}</span>
                    </div>
                @endif

                <x-secondary-button wire:click="printBill" wire:loading.attr="disabled" wire:target="printBill"
                    class="w-full justify-center py-2.5">
                    <span wire:loading.remove wire:target="printBill">{{ __('Cetak Bill') }}</span>
                    <span wire:loading wire:target="printBill">{{ __('Menyiapkan...') }}</span>
                </x-secondary-button>
                <p class="text-xs text-slate-400 dark:text-slate-500 text-center -mt-2">
                    {{ __('Tunjukkan ke customer sebelum menerima pembayaran.') }}</p>

                <x-primary-button wire:click="checkout"
                    wire:confirm="Pembayaran sudah diterima dari customer? Transaksi akan langsung disimpan dan stok berkurang."
                    wire:loading.attr="disabled" wire:target="checkout" class="w-full justify-center py-3">
                    <span wire:loading.remove wire:target="checkout">{{ __('Konfirmasi Pembayaran') }}</span>
                    <span wire:loading wire:target="checkout">{{ __('Memproses...') }}</span>
                </x-primary-button>
            </div>
        @endif
    </div>

    <!-- Product Detail Modal -->
    @if ($viewingProductId && $this->viewingProduct)
        @php $viewingProduct = $this->viewingProduct; @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6"
            x-data="{ qty: 1, max: {{ $viewingProduct->is_unlimited_stock ? 'Infinity' : max(1, (int) $viewingProduct->stock_qty) }} }">
            <div class="fixed inset-0 bg-slate-900/60" wire:click="closeProductModal" wire:transition.opacity></div>
            <div
                wire:transition.scale.origin.top
                class="relative mb-6 bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-soft sm:max-w-sm sm:mx-auto">
                <x-product-thumb :product="$viewingProduct" class="h-40 w-full rounded-none" />

                <div class="p-6">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $viewingProduct->name }}
                    </h3>
                    @if ($viewingProduct->description)
                        <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                            {{ $viewingProduct->description }}</p>
                    @endif
                    <p class="mt-2 text-lg font-semibold text-brand-600 dark:text-brand-400">Rp
                        {{ number_format($viewingProduct->price, 0, ',', '.') }}</p>

                    <div class="mt-4 flex items-center justify-center gap-4">
                        <button type="button" x-on:click="qty = Math.max(1, qty - 1)"
                            class="w-9 h-9 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-medium text-lg">-</button>
                        <span
                            class="w-10 text-center text-lg font-semibold text-slate-900 dark:text-slate-100" x-text="qty"></span>
                        <button type="button" x-on:click="qty = Math.min(max, qty + 1)"
                            class="w-9 h-9 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-medium text-lg">+</button>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" wire:click="closeProductModal" wire:loading.attr="disabled"
                            wire:target="confirmAddToCart"
                            class="px-4 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 disabled:opacity-40">{{ __('Batal') }}</button>
                        <x-primary-button type="button" x-on:click="$wire.confirmAddToCart(qty)" wire:loading.attr="disabled"
                            wire:target="confirmAddToCart">
                            <span wire:loading.remove wire:target="confirmAddToCart">{{ __('Tambahkan') }}</span>
                            <span wire:loading wire:target="confirmAddToCart">{{ __('Menambahkan...') }}</span>
                        </x-primary-button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <x-toast on="product-added" />
</div>
