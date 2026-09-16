<div>
    @if ($confirmedCode)
        <div wire:transition class="bg-white dark:bg-slate-900 rounded-2xl shadow-card border border-slate-200/70 dark:border-slate-800 p-8 text-center max-w-md mx-auto">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
            </span>

            <h2 class="mt-4 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Pesanan Diterima') }}</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Tunjukkan atau sebutkan kode ini ke kasir untuk menyelesaikan pembayaran.') }}</p>

            <div class="mt-6 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl py-6">
                <p class="text-xs uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ __('Kode Pesanan') }}</p>
                <p class="mt-2 text-4xl font-bold tracking-[0.3em] text-brand-600 dark:text-brand-400">{{ $confirmedCode }}</p>
                @if ($this->confirmedCodeBarcode)
                    <div class="mt-3 bg-white p-2 inline-block rounded-lg">{!! $this->confirmedCodeBarcode !!}</div>
                    <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">{{ __('Bisa langsung di-scan pakai barcode scanner di kasir.') }}</p>
                @endif
            </div>

            <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">{{ __('Total') }}: <span class="font-semibold text-slate-900 dark:text-slate-100">Rp {{ number_format($confirmedTotal, 0, ',', '.') }}</span></p>
            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ __('Kode berlaku :minutes menit.', ['minutes' => \App\Models\SelfOrder::VALID_MINUTES]) }}</p>

            <x-primary-button wire:click="newOrder" class="mt-6 w-full justify-center">
                {{ __('Pesan Lagi') }}
            </x-primary-button>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Product picker -->
            <div class="lg:col-span-2 space-y-4">
                <div class="relative">
                    <svg wire:loading.remove wire:target="search" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <svg wire:loading wire:target="search" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-brand-500 dark:text-brand-400 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <x-text-input wire:model.live.debounce.300ms="search" type="text" class="w-full pl-10" placeholder="Cari menu..." />
                </div>

                @if ($categories->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            type="button"
                            wire:click="selectCategory(null)"
                            class="px-3 py-1.5 rounded-full text-xs font-semibold border transition {{ is_null($activeCategoryId) ? 'bg-slate-900 border-slate-900 text-white dark:bg-slate-100 dark:border-slate-100 dark:text-slate-900' : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300 dark:bg-slate-800/60 dark:border-slate-700 dark:text-slate-400' }}"
                        >
                            {{ __('Semua') }}
                        </button>
                        @foreach ($categories as $category)
                            <button
                                type="button"
                                wire:click="selectCategory({{ $category->id }})"
                                class="px-3 py-1.5 rounded-full text-xs font-semibold border transition {{ $activeCategoryId === $category->id ? 'bg-slate-900 border-slate-900 text-white dark:bg-slate-100 dark:border-slate-100 dark:text-slate-900' : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300 dark:bg-slate-800/60 dark:border-slate-700 dark:text-slate-400' }}"
                            >
                                {{ $category->name }}
                            </button>
                        @endforeach
                    </div>
                @endif

                @if ($tags->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($tags as $tag)
                            <button
                                type="button"
                                wire:click="toggleTag({{ $tag->id }})"
                                class="px-2.5 py-1 rounded-full text-xs font-medium border transition {{ in_array($tag->id, $activeTags) ? 'bg-brand-600 border-brand-600 text-white dark:bg-brand-500 dark:border-brand-500' : 'bg-white border-slate-200 text-slate-500 hover:border-brand-300 dark:bg-slate-800/60 dark:border-slate-700 dark:text-slate-400' }}"
                            >
                                {{ $tag->name }}
                            </button>
                        @endforeach
                    </div>
                @endif

                @error('cart')
                    <p class="text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror

                <div wire:loading.class="opacity-40" wire:target="search,selectCategory,toggleTag" class="transition-opacity duration-150">
                @if ($productGroups)
                    <div class="space-y-5">
                        @foreach ($categories as $category)
                            @php $items = $productGroups->get($category->id); @endphp
                            @if ($items && $items->isNotEmpty())
                                <div>
                                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-2">{{ $category->name }}</h4>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                        @foreach ($items as $product)
                                            @include('livewire.self-order.product-card', ['product' => $product])
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach

                        @php $uncategorized = $productGroups->get(0); @endphp
                        @if ($uncategorized && $uncategorized->isNotEmpty())
                            <div>
                                <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-2">{{ __('Tanpa Kategori') }}</h4>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    @foreach ($uncategorized as $product)
                                        @include('livewire.self-order.product-card', ['product' => $product])
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($products->isEmpty())
                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Menu tidak ditemukan.') }}</p>
                        @endif
                    </div>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @forelse ($products as $product)
                            @include('livewire.self-order.product-card', ['product' => $product])
                        @empty
                            <p class="col-span-full text-sm text-slate-500 dark:text-slate-400">{{ __('Menu tidak ditemukan.') }}</p>
                        @endforelse
                    </div>
                @endif
                </div>
            </div>

            <!-- Cart -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-card border border-slate-200/70 dark:border-slate-800 p-5 flex flex-col h-fit lg:sticky lg:top-24">
                <h3 class="font-semibold text-slate-900 dark:text-slate-100 mb-3">{{ __('Pesanan Anda') }}</h3>

                <div class="space-y-3 mb-3">
                    <div>
                        <x-input-label for="customerName" value="Nama (opsional)" />
                        <x-text-input wire:model.blur="customerName" id="customerName" type="text" class="block w-full" placeholder="mis. Budi" />
                    </div>
                    <div>
                        <x-input-label for="orderNote" value="Catatan (opsional)" />
                        <x-text-input wire:model.blur="orderNote" id="orderNote" type="text" class="block w-full" placeholder="mis. dibungkus terpisah" />
                    </div>
                </div>

                <div class="flex-1 space-y-2 max-h-96 overflow-y-auto">
                    @forelse ($cart as $productId => $item)
                        <div wire:key="cart-{{ $productId }}" class="border-b border-slate-100 dark:border-slate-800 pb-2.5">
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex-1 pr-2">
                                    <div class="text-slate-900 dark:text-slate-100">{{ $item['name'] }}</div>
                                    <div class="text-slate-500 dark:text-slate-400">Rp {{ number_format($item['price'], 0, ',', '.') }}</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" wire:click="decrementQty({{ $productId }})" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-medium">-</button>
                                    <span class="w-6 text-center font-medium text-slate-900 dark:text-slate-100">{{ $item['qty'] }}</span>
                                    <button type="button" wire:click="incrementQty({{ $productId }})" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-medium">+</button>
                                </div>
                            </div>
                            <input
                                type="text"
                                wire:model.blur="cart.{{ $productId }}.note"
                                placeholder="Catatan (opsional)"
                                class="mt-1.5 w-full text-xs border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg placeholder:text-slate-400 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
                            />
                        </div>
                    @empty
                        <p class="text-sm text-slate-400 dark:text-slate-500 py-6 text-center">{{ __('Belum ada item.') }}</p>
                    @endforelse
                </div>

                <div class="mt-4 space-y-3 border-t border-slate-100 dark:border-slate-800 pt-4">
                    <div class="flex justify-between text-base font-semibold">
                        <span class="text-slate-900 dark:text-slate-100">{{ __('Total') }}</span>
                        <span class="text-brand-600 dark:text-brand-400">Rp {{ number_format($this->subtotal, 0, ',', '.') }}</span>
                    </div>

                    <x-primary-button
                        wire:click="confirmOrder"
                        wire:loading.attr="disabled"
                        wire:target="confirmOrder"
                        class="w-full justify-center py-3"
                    >
                        <span wire:loading.remove wire:target="confirmOrder">{{ __('Konfirmasi Pesanan') }}</span>
                        <span wire:loading wire:target="confirmOrder">{{ __('Memproses...') }}</span>
                    </x-primary-button>
                    <p class="text-xs text-slate-400 dark:text-slate-500 text-center">{{ __('Anda akan mendapat kode untuk membayar di kasir.') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Product Detail Modal -->
    @if ($viewingProductId && $this->viewingProduct)
        @php $viewingProduct = $this->viewingProduct; @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6"
            x-data="{ qty: 1, note: '', max: {{ $viewingProduct->is_unlimited_stock ? 'Infinity' : max(1, (int) $viewingProduct->stock_qty) }} }">
            <div class="fixed inset-0 bg-slate-900/60" wire:click="closeProductModal"></div>
            <div class="relative mb-6 bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-soft sm:max-w-sm sm:mx-auto">
                <x-product-thumb :product="$viewingProduct" class="h-40 w-full rounded-none" />

                <div class="p-6">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $viewingProduct->name }}</h3>
                    @if ($viewingProduct->description)
                        <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">{{ $viewingProduct->description }}</p>
                    @endif
                    <p class="mt-2 text-lg font-semibold text-brand-600 dark:text-brand-400">Rp {{ number_format($viewingProduct->price, 0, ',', '.') }}</p>

                    <div class="mt-4 flex items-center justify-center gap-4">
                        <button type="button" x-on:click="qty = Math.max(1, qty - 1)" class="w-9 h-9 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-medium text-lg">-</button>
                        <span class="w-10 text-center text-lg font-semibold text-slate-900 dark:text-slate-100" x-text="qty"></span>
                        <button type="button" x-on:click="qty = Math.min(max, qty + 1)" class="w-9 h-9 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-medium text-lg">+</button>
                    </div>

                    <div class="mt-4">
                        <x-input-label for="modalNote" value="Catatan (opsional)" />
                        <input x-model="note" id="modalNote" type="text" placeholder="mis. tanpa gula, pedas level 2" class="mt-1 block w-full border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 placeholder:text-slate-400 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500" />
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" wire:click="closeProductModal" wire:loading.attr="disabled" wire:target="confirmAddToCart" class="px-4 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 disabled:opacity-40">{{ __('Batal') }}</button>
                        <x-primary-button type="button" x-on:click="$wire.confirmAddToCart(qty, note)" wire:loading.attr="disabled" wire:target="confirmAddToCart">
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
