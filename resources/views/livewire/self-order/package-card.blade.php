<button
    type="button"
    wire:click="addPackageToCart({{ $package->id }})"
    wire:loading.attr="disabled"
    wire:target="addPackageToCart({{ $package->id }})"
    @disabled(! $package->isAvailable())
    class="text-left bg-white dark:bg-slate-900 rounded-xl shadow-card p-3 border-2 border-brand-200 dark:border-brand-700 hover:border-brand-400 dark:hover:border-brand-500 hover:shadow-md disabled:opacity-40 disabled:cursor-not-allowed transition"
>
    @if ($package->imageUrl())
        <img src="{{ $package->imageUrl() }}" class="h-24 w-full object-cover rounded-lg">
    @else
        <div class="h-24 w-full rounded-lg bg-brand-50 dark:bg-brand-500/10 flex items-center justify-center text-brand-400 dark:text-brand-500">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6.75 3.75h3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
        </div>
    @endif
    <span class="inline-flex items-center px-1.5 py-0.5 mt-2 rounded-full text-[10px] font-bold bg-brand-600 text-white">{{ __('PAKET') }}</span>
    <div class="font-medium text-slate-900 dark:text-slate-100 mt-1">{{ $package->name }}</div>
    <div class="text-sm text-brand-600 dark:text-brand-400 font-semibold mt-1">Rp {{ number_format($package->price, 0, ',', '.') }}</div>
    @if (! $package->isAvailable())
        <div class="text-xs text-rose-500 dark:text-rose-400 mt-1">{{ __('Habis') }}</div>
    @endif
</button>
