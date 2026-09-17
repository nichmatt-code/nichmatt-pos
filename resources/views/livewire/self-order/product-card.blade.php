<button
    type="button"
    wire:click="openProductModal({{ $product->id }})"
    wire:loading.attr="disabled"
    wire:target="openProductModal"
    @disabled(! $product->isAvailable())
    class="text-left bg-white dark:bg-slate-900 rounded-xl shadow-card p-3 border border-slate-200/70 dark:border-slate-800 hover:border-brand-300 dark:hover:border-brand-600 hover:shadow-md disabled:opacity-40 disabled:cursor-not-allowed transition"
>
    <x-product-thumb :product="$product" />
    <div class="font-medium text-slate-900 dark:text-slate-100 mt-2">{{ $product->name }}</div>
    <div class="text-sm text-brand-600 dark:text-brand-400 font-semibold mt-1">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
    @if (! $product->isAvailable())
        <div class="text-xs text-rose-500 dark:text-rose-400 mt-1">{{ __('Habis') }}</div>
    @endif
</button>
