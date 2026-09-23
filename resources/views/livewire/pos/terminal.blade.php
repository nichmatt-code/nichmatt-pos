<div x-data x-on:bill-ready.window="window.open('{{ route('pos.bill') }}', '_blank')"
    x-on:loss-ready.window="window.open('{{ url('loss-records') }}/' + $event.detail.lossId + '/receipt', '_blank')"
    class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Product picker -->
    <div class="lg:col-span-2 space-y-4">
        <div class="flex gap-2">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-brand-500 dark:text-brand-400"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 6h.01M4 12h.01M4 18h.01M8 6h1m-1 6h1m-1 6h1m4-18v18m4-18v6m0 6v6" />
                </svg>
                <input wire:model="barcodeInput" wire:keydown.enter.prevent="scanBarcode" type="text" autofocus
                    placeholder="Scan barcode produk..."
                    class="w-full pl-10 border-slate-200 focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 placeholder:text-slate-400 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-100 dark:placeholder:text-slate-500" />
            </div>
            <a href="{{ route('self-order.qr') }}" target="_blank" title="{{ __('QR Self Order') }}"
                class="shrink-0 inline-flex items-center justify-center w-11 rounded-lg border border-slate-200 bg-white text-slate-500 hover:border-brand-300 hover:text-brand-600 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-400 dark:hover:text-brand-400 transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 4.5A.75.75 0 014.5 3.75h4.5a.75.75 0 01.75.75v4.5a.75.75 0 01-.75.75h-4.5a.75.75 0 01-.75-.75v-4.5zM3.75 15a.75.75 0 01.75-.75h4.5a.75.75 0 01.75.75v4.5a.75.75 0 01-.75.75h-4.5A.75.75 0 013.75 19.5V15zM14.25 4.5a.75.75 0 01.75-.75h4.5a.75.75 0 01.75.75v4.5a.75.75 0 01-.75.75h-4.5a.75.75 0 01-.75-.75v-4.5z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6.75h1.5v1.5H6v-1.5zM6 17.25h1.5v1.5H6v-1.5zM16.5 6.75H18v1.5h-1.5v-1.5zM14.25 14.25h2.25v2.25M14.25 19.5h2.25M19.5 14.25v2.25M19.5 19.5v.01M17.25 17.25h.01M14.25 17.25h.01" />
                </svg>
            </a>
            <button type="button" wire:click="openLossModal" title="{{ __('Catat Kerugian') }}"
                class="shrink-0 inline-flex items-center justify-center w-11 rounded-lg border border-slate-200 bg-white text-slate-500 hover:border-rose-300 hover:text-rose-600 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-400 dark:hover:text-rose-400 transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-8.25 3.75h.008v.008h-.008v-.008z" />
                </svg>
            </button>
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

        @if ($packages->isNotEmpty())
            <div>
                <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-2">{{ __('Paket') }}</h4>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach ($packages as $package)
                        @include('livewire.pos.package-card', ['package' => $package])
                    @endforeach
                </div>
            </div>
        @endif

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
    <div id="cart-panel"
        class="bg-white dark:bg-slate-900 rounded-2xl shadow-card border border-slate-200/70 dark:border-slate-800 p-5 flex flex-col h-fit lg:sticky lg:top-24 scroll-mt-24">
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
                    @if ($waLink = \App\Services\WhatsAppReceiptFormatter::waLink($lastTransaction))
                        <a href="{{ $waLink }}" target="_blank" rel="noopener"
                            class="inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 rounded-lg font-medium text-sm text-white transition">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.29-1.39a9.87 9.87 0 0 0 4.75 1.21h.01c5.46 0 9.9-4.45 9.9-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2Zm5.8 14.09c-.24.68-1.4 1.3-1.93 1.37-.5.08-1.11.11-1.79-.11-.41-.13-.94-.3-1.62-.6-2.84-1.23-4.7-4.1-4.84-4.29-.14-.19-1.16-1.55-1.16-2.95 0-1.4.73-2.09 1-2.38.25-.27.55-.34.73-.34.19 0 .37 0 .53.01.17.01.4-.06.62.48.24.58.81 2 .88 2.15.07.15.12.32.02.51-.09.19-.14.31-.28.48-.14.16-.29.36-.42.48-.14.13-.28.28-.12.55.16.27.7 1.16 1.51 1.88 1.04.93 1.91 1.22 2.18 1.36.27.14.43.12.59-.07.16-.19.68-.79.86-1.06.18-.27.36-.22.6-.13.25.09 1.58.75 1.85.88.27.14.45.2.52.32.07.11.07.66-.17 1.34Z"/></svg>
                            {{ __('Kirim via WhatsApp') }}
                        </a>
                    @endif
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
                    <x-input-label for="customerPhone" value="Nomor WhatsApp (opsional)" />
                    <x-text-input wire:model.blur="customerPhone" id="customerPhone" type="text"
                        class="block w-full" placeholder="mis. 081234567890" />
                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ __('Diisi otomatis kalau memilih member. Isi manual untuk kirim struk lewat WhatsApp ke pelanggan.') }}</p>
                    <x-input-error :messages="$errors->get('customerPhone')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="orderNote" value="Catatan Order (opsional)" />
                    <x-text-input wire:model.blur="orderNote" id="orderNote" type="text" class="block w-full"
                        placeholder="mis. dibungkus terpisah" />
                </div>
            </div>

            <div class="flex-1 space-y-2 max-h-96 overflow-y-auto">
                @forelse ($cart as $cartKey => $item)
                    <div wire:key="cart-{{ $cartKey }}"
                        class="border-b border-slate-100 dark:border-slate-800 pb-2.5">
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex-1 pr-2">
                                <div class="text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                                    {{ $item['name'] }}
                                    @if (($item['type'] ?? 'product') === 'package')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">{{ __('PAKET') }}</span>
                                    @elseif (! empty($item['is_gift']))
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">{{ __('GRATIS') }}</span>
                                    @endif
                                </div>
                                @if ($this->store->allow_price_edit && ($item['type'] ?? 'product') === 'product' && empty($item['is_gift']))
                                    <div class="flex items-center gap-1 mt-0.5">
                                        <span class="text-xs text-slate-400 dark:text-slate-500">Rp</span>
                                        <input type="number" min="0"
                                            wire:model.blur="cart.{{ $cartKey }}.price"
                                            class="w-24 text-xs border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg py-1 px-2 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100" />
                                    </div>
                                @else
                                    <div class="text-slate-500 dark:text-slate-400">Rp
                                        {{ number_format($item['price'], 0, ',', '.') }}</div>
                                @endif
                            </div>
                            @if (! empty($item['is_gift']))
                                <span class="text-xs text-slate-400 dark:text-slate-500 px-1">x{{ $item['qty'] }}</span>
                            @else
                                <div class="flex items-center gap-2">
                                    <button type="button" wire:click="decrementQty('{{ $cartKey }}')"
                                        class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-medium">-</button>
                                    <span
                                        class="w-6 text-center font-medium text-slate-900 dark:text-slate-100">{{ $item['qty'] }}</span>
                                    <button type="button" wire:click="incrementQty('{{ $cartKey }}')"
                                        class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-medium">+</button>
                                </div>
                            @endif
                        </div>
                        @if (empty($item['is_gift']))
                            <input type="text" wire:model.blur="cart.{{ $cartKey }}.note"
                                placeholder="Catatan (opsional), mis. tanpa gula"
                                class="mt-1.5 w-full text-xs border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg placeholder:text-slate-400 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500" />
                        @endif
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

                @if ($this->appliedCoupon)
                    @php $appliedCoupon = $this->appliedCoupon; @endphp
                    <div class="flex items-center justify-between gap-2 rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50/60 dark:bg-emerald-500/10 px-3 py-2.5">
                        <div>
                            <div class="text-xs font-semibold text-emerald-700 dark:text-emerald-400">{{ __('Kupon') }}: {{ $appliedCoupon->code }}</div>
                            <div class="text-xs text-emerald-600 dark:text-emerald-500">
                                @if ($appliedCoupon->hasDiscount())
                                    -Rp {{ number_format($this->couponDiscountAmount, 0, ',', '.') }}
                                @endif
                                @if ($appliedCoupon->hasGift())
                                    {{ $appliedCoupon->hasDiscount() ? '&' : '' }} {{ __('Gratis :name', ['name' => $appliedCoupon->giftProduct?->name]) }}
                                @endif
                            </div>
                        </div>
                        <button type="button" wire:click="removeCoupon" class="text-xs font-medium text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300">{{ __('Hapus') }}</button>
                    </div>
                @else
                    <div>
                        <x-input-label for="couponCodeInput" value="Kode Kupon (opsional)" />
                        <div class="mt-1 flex gap-2">
                            <input wire:model="couponCodeInput" wire:keydown.enter.prevent="applyCoupon" id="couponCodeInput" type="text"
                                placeholder="mis. ULTAH25"
                                class="block w-full uppercase tracking-widest border-slate-200 focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 placeholder:text-slate-400 placeholder:tracking-normal dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-100 dark:placeholder:text-slate-500" />
                            <x-secondary-button type="button" wire:click="applyCoupon" wire:loading.attr="disabled" wire:target="applyCoupon" class="shrink-0">
                                <span wire:loading.remove wire:target="applyCoupon">{{ __('Terapkan') }}</span>
                                <span wire:loading wire:target="applyCoupon">{{ __('Mengecek...') }}</span>
                            </x-secondary-button>
                        </div>
                        <x-input-error :messages="$errors->get('couponCodeInput')" class="mt-1" />
                    </div>
                @endif

                @if ($this->couponDiscountAmount > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500 dark:text-slate-400">{{ __('Diskon Kupon') }}</span>
                        <span class="text-emerald-600 dark:text-emerald-400">-Rp {{ number_format($this->couponDiscountAmount, 0, ',', '.') }}</span>
                    </div>
                @endif

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

                @if ($this->store->canAcceptOnlinePayments())
                    <button type="button" wire:click="payWithQrisOnline" wire:loading.attr="disabled"
                        wire:target="payWithQrisOnline"
                        class="w-full inline-flex justify-center items-center gap-1.5 px-4 py-2.5 border border-emerald-300 dark:border-emerald-700 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 rounded-lg font-medium text-sm transition disabled:opacity-50">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 4.5A.75.75 0 014.5 3.75h4.5a.75.75 0 01.75.75v4.5a.75.75 0 01-.75.75h-4.5a.75.75 0 01-.75-.75v-4.5zM3.75 15a.75.75 0 01.75-.75h4.5a.75.75 0 01.75.75v4.5a.75.75 0 01-.75.75h-4.5A.75.75 0 013.75 19.5V15zM14.25 4.5a.75.75 0 01.75-.75h4.5a.75.75 0 01.75.75v4.5a.75.75 0 01-.75.75h-4.5a.75.75 0 01-.75-.75v-4.5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6.75h1.5v1.5H6v-1.5zM6 17.25h1.5v1.5H6v-1.5zM16.5 6.75H18v1.5h-1.5v-1.5zM14.25 14.25h2.25v2.25M14.25 19.5h2.25M19.5 14.25v2.25M19.5 19.5v.01M17.25 17.25h.01M14.25 17.25h.01" />
                        </svg>
                        <span wire:loading.remove wire:target="payWithQrisOnline">{{ __('Bayar QRIS Online') }}</span>
                        <span wire:loading wire:target="payWithQrisOnline">{{ __('Membuat QR...') }}</span>
                    </button>
                    <x-input-error :messages="$errors->get('qrisPayment')" class="-mt-2" />
                @endif
            </div>
        @endif
    </div>

    <!-- Product Detail Modal -->
    @if ($viewingProductId && $this->viewingProduct)
        @php $viewingProduct = $this->viewingProduct; @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6"
            x-data="{ qty: 1, note: '', max: {{ $viewingProduct->is_unlimited_stock ? 'Infinity' : max(1, (int) $viewingProduct->stock_qty) }} }">
            <div class="fixed inset-0 bg-slate-900/60" wire:click="closeProductModal"></div>
            <div
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

                    <div class="mt-4">
                        <x-input-label for="modalNote" value="Catatan (opsional)" />
                        <input x-model="note" id="modalNote" type="text" placeholder="mis. tanpa gula, pedas level 2"
                            class="mt-1 block w-full border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 placeholder:text-slate-400 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500" />
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" wire:click="closeProductModal" wire:loading.attr="disabled"
                            wire:target="confirmAddToCart"
                            class="px-4 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 disabled:opacity-40">{{ __('Batal') }}</button>
                        <x-primary-button type="button" x-on:click="$wire.confirmAddToCart(qty, note)" wire:loading.attr="disabled"
                            wire:target="confirmAddToCart">
                            <span wire:loading.remove wire:target="confirmAddToCart">{{ __('Tambahkan') }}</span>
                            <span wire:loading wire:target="confirmAddToCart">{{ __('Menambahkan...') }}</span>
                        </x-primary-button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- QRIS Online Payment Modal -->
    @if ($qrisPaymentId && $this->qrisPayment)
        @php $qrisPayment = $this->qrisPayment; @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6"
            @if ($qrisPayment->isPending()) wire:poll.3s="checkQrisPaymentStatus" @endif>
            <div class="fixed inset-0 bg-slate-900/60"></div>
            <div
                class="relative mb-6 bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-soft sm:max-w-sm sm:mx-auto">
                <div class="p-6 text-center">
                    @if ($qrisPayment->isPending())
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                            {{ __('Scan untuk Bayar') }}</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ __('Minta customer scan QR ini pakai e-wallet atau m-banking apa saja.') }}</p>

                        @if ($qrisPayment->qr_url)
                            <img src="{{ $qrisPayment->qr_url }}" alt="QRIS" class="mt-4 mx-auto h-56 w-56">
                        @endif

                        <p class="mt-4 text-2xl font-bold text-slate-900 dark:text-slate-100">Rp
                            {{ number_format($qrisPayment->amount, 0, ',', '.') }}</p>

                        <div class="mt-4 flex items-center justify-center gap-2 text-sm text-brand-600 dark:text-brand-400">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{ __('Menunggu pembayaran...') }}
                        </div>

                        <button type="button" wire:click="cancelQrisPayment"
                            class="mt-6 text-sm text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">
                            {{ __('Batalkan') }}
                        </button>
                    @elseif ($qrisPayment->status === 'expired')
                        <p class="text-rose-600 dark:text-rose-400 font-medium">{{ __('QR sudah kedaluwarsa.') }}</p>
                        <button type="button" wire:click="cancelQrisPayment"
                            class="mt-4 inline-flex items-center px-4 py-2.5 bg-slate-100 dark:bg-slate-800 rounded-lg font-medium text-sm text-slate-700 dark:text-slate-200">
                            {{ __('Tutup') }}
                        </button>
                    @else
                        <p class="text-rose-600 dark:text-rose-400 font-medium">
                            {{ __('Pembayaran dibatalkan atau gagal.') }}</p>
                        <button type="button" wire:click="cancelQrisPayment"
                            class="mt-4 inline-flex items-center px-4 py-2.5 bg-slate-100 dark:bg-slate-800 rounded-lg font-medium text-sm text-slate-700 dark:text-slate-200">
                            {{ __('Tutup') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Loss Record Modal -->
    @if ($showLossModal)
        <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
            <div class="fixed inset-0 bg-slate-900/60" wire:click="closeLossModal"></div>
            <div
                class="relative mb-6 bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-soft sm:max-w-md sm:mx-auto">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Catat Kerugian') }}</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ __('Untuk barang rusak, makanan jatuh, atau kerugian lain. Stok akan berkurang tanpa tercatat sebagai penjualan.') }}
                    </p>

                    <div class="mt-4 relative">
                        <x-input-label for="lossSearch" value="Cari Produk" />
                        <x-text-input wire:model.live.debounce.300ms="lossSearch" id="lossSearch" type="text"
                            class="block w-full" placeholder="Ketik nama produk..." autocomplete="off" />
                        @if ($this->lossSearchResults->isNotEmpty())
                            <div
                                class="absolute z-10 mt-1 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg overflow-hidden">
                                @foreach ($this->lossSearchResults as $result)
                                    <button type="button" wire:click="addLossItem({{ $result->id }})"
                                        class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                                        <div class="font-medium text-slate-900 dark:text-slate-100">
                                            {{ $result->name }}</div>
                                        <div class="text-xs text-slate-400 dark:text-slate-500">Modal: Rp
                                            {{ number_format($result->cost_price, 0, ',', '.') }}</div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <x-input-error :messages="$errors->get('lossItems')" class="mt-2" />

                    <div class="mt-3 space-y-2 max-h-56 overflow-y-auto">
                        @forelse ($lossItems as $productId => $item)
                            <div wire:key="loss-item-{{ $productId }}"
                                class="flex items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                                <div class="flex-1">
                                    <div class="text-sm text-slate-900 dark:text-slate-100">{{ $item['name'] }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">Rp
                                        {{ number_format($item['cost_price'], 0, ',', '.') }} / {{ __('unit') }}</div>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button type="button" wire:click="decrementLossQty({{ $productId }})"
                                        class="w-6 h-6 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm">-</button>
                                    <span
                                        class="w-6 text-center text-sm font-medium text-slate-900 dark:text-slate-100">{{ $item['qty'] }}</span>
                                    <button type="button" wire:click="incrementLossQty({{ $productId }})"
                                        class="w-6 h-6 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm">+</button>
                                </div>
                                <button type="button" wire:click="removeLossItem({{ $productId }})"
                                    class="text-rose-500 hover:text-rose-700 dark:text-rose-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @empty
                            <p class="text-sm text-slate-400 dark:text-slate-500 py-3 text-center">
                                {{ __('Belum ada produk dipilih.') }}</p>
                        @endforelse
                    </div>

                    @if (! empty($lossItems))
                        <div class="mt-2 flex justify-between text-sm font-semibold">
                            <span class="text-slate-900 dark:text-slate-100">{{ __('Total Nilai Kerugian') }}</span>
                            <span class="text-rose-600 dark:text-rose-400">Rp
                                {{ number_format($this->lossTotalCost, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    <div class="mt-4">
                        <x-input-label for="lossReason" value="Alasan" />
                        <x-text-input wire:model="lossReason" id="lossReason" type="text" class="block w-full"
                            placeholder="mis. Gelas pecah, ayam jatuh, kadaluarsa" />
                        <x-input-error :messages="$errors->get('lossReason')" class="mt-1" />
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" wire:click="closeLossModal"
                            class="px-4 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">{{ __('Batal') }}</button>
                        <button type="button" wire:click="submitLoss"
                            wire:confirm="Catat kerugian ini? Stok akan berkurang secara permanen."
                            wire:loading.attr="disabled" wire:target="submitLoss"
                            class="inline-flex items-center px-4 py-2.5 bg-rose-600 hover:bg-rose-700 rounded-lg font-medium text-sm text-white shadow-sm transition disabled:opacity-50">
                            <span wire:loading.remove wire:target="submitLoss">{{ __('Catat & Cetak') }}</span>
                            <span wire:loading wire:target="submitLoss">{{ __('Menyimpan...') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (! empty($cart) && ! $lastTransaction)
        <div
            x-data="{ show: true }"
            x-init="
                let target = document.getElementById('cart-panel');
                let observer = new IntersectionObserver((entries) => { show = ! entries[0].isIntersecting; }, { threshold: 0.15 });
                if (target) observer.observe(target);
                $el.__cartPanelObserver = observer;
            "
            x-on:destroy="$el.__cartPanelObserver && $el.__cartPanelObserver.disconnect()"
        >
            <button type="button" x-show="show" x-transition.opacity
                x-on:click="document.getElementById('cart-panel').scrollIntoView({ behavior: 'smooth', block: 'start' })"
                class="lg:hidden fixed bottom-20 inset-x-4 z-30 flex items-center justify-between gap-3 rounded-2xl bg-brand-600 dark:bg-brand-500 text-white shadow-lg px-4 py-3.5">
                <span class="flex items-center gap-2 text-sm font-semibold">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                    </svg>
                    {{ collect($cart)->sum('qty') }} {{ __('item') }}
                </span>
                <span class="flex items-center gap-1.5 text-sm font-semibold">
                    {{ __('Lihat Pesanan') }} · Rp {{ number_format($this->total, 0, ',', '.') }}
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </span>
            </button>
        </div>
    @endif

    <x-toast on="product-added" />
</div>
