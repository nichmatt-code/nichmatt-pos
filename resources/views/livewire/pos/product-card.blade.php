<button
    type="button"
    wire:click="openProductModal({{ $product->id }})"
    wire:loading.attr="disabled"
    wire:target="openProductModal"
    @disabled(! $product->isAvailable())
    class="text-left bg-white dark:bg-slate-900 rounded-xl shadow-card p-3 border border-slate-200/70 dark:border-slate-800 hover:border-brand-300 dark:hover:border-brand-600 hover:shadow-md disabled:opacity-40 disabled:cursor-not-allowed transition"
>
    @php
        $discountPromo = ($promosByProduct ?? collect())->get($product->id);
        $giftPromos = ($giftPromosByProduct ?? collect())->get($product->id);
    @endphp
    <div class="relative">
        @if ($showImage ?? true)
            <x-product-thumb :product="$product" />
        @endif
        @if ($discountPromo || $giftPromos)
            <span class="absolute top-1 left-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-600 text-white shadow">
                {{ __('PROMO') }}
            </span>
        @endif
    </div>
    <div class="font-medium text-slate-900 dark:text-slate-100 {{ ($showImage ?? true) ? 'mt-2' : '' }}">{{ $product->name }}</div>
    @if ($discountPromo)
        <div class="flex items-center gap-1.5 mt-1">
            <span class="text-xs text-slate-400 dark:text-slate-500 line-through">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
            <span class="text-sm text-rose-600 dark:text-rose-400 font-semibold">Rp {{ number_format($discountPromo->discountedPriceFor($product->price), 0, ',', '.') }}</span>
        </div>
    @else
        <div class="text-sm text-brand-600 dark:text-brand-400 font-semibold mt-1">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
    @endif
    @if ($giftPromos)
        @foreach ($giftPromos as $giftPromo)
            <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium mt-0.5">{{ $giftPromo->badgeLabel() }}</div>
        @endforeach
    @endif
    @if ($product->is_out_of_stock)
        <div class="text-xs text-rose-500 dark:text-rose-400 mt-1">{{ __('Habis') }}</div>
    @elseif ($product->is_unlimited_stock)
        <div class="text-xs text-brand-500 dark:text-brand-400 mt-1">{{ __('Stok tersedia') }}</div>
    @else
        <div class="text-xs {{ $product->isLowStock() ? 'text-rose-500 dark:text-rose-400' : 'text-slate-400 dark:text-slate-500' }} mt-1">
            Stok: {{ $product->stock_qty }} {{ $product->unit }}
        </div>
    @endif
</button>
