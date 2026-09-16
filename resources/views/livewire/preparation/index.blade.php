<div wire:poll.5s class="space-y-8">
    <!-- Pending -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Perlu Diproses') }}</h3>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                {{ $pending->count() }}
            </span>
        </div>

        @if ($pending->isEmpty())
            <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-8 text-center">
                <p class="text-sm text-slate-400 dark:text-slate-500">{{ __('Tidak ada pesanan yang perlu diproses.') }}</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5">
                @foreach ($pending as $transaction)
                    <div
                        wire:click="openDetail({{ $transaction->id }})"
                        wire:key="pending-{{ $transaction->id }}"
                        class="cursor-pointer bg-white dark:bg-slate-900 border border-amber-200 dark:border-amber-900/50 shadow-card rounded-xl p-3 hover:border-amber-400 dark:hover:border-amber-600 hover:shadow-md transition"
                    >
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500 truncate">{{ $transaction->transaction_no }}</span>
                            <span class="shrink-0 text-[10px] text-slate-400 dark:text-slate-500">{{ $transaction->created_at->diffForHumans(null, true) }}</span>
                        </div>
                        @if ($transaction->customer_name)
                            <p class="mt-0.5 text-sm font-semibold text-slate-900 dark:text-slate-100 truncate">{{ $transaction->customer_name }}</p>
                        @endif

                        <ul class="mt-1.5 space-y-1">
                            @foreach ($transaction->items as $item)
                                <li class="text-xs leading-snug text-slate-600 dark:text-slate-300 truncate">
                                    <span class="font-medium text-slate-800 dark:text-slate-100">{{ $item->qty }}x</span> {{ $item->product_name }}
                                    @if ($item->note)
                                        <span class="block text-xs font-medium text-brand-600 dark:text-brand-400">{{ $item->note }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        @if ($transaction->note)
                            <p class="mt-1.5 text-xs font-medium text-amber-700 dark:text-amber-400 truncate">{{ $transaction->note }}</p>
                        @endif

                        <button
                            type="button"
                            wire:click.stop="markPrepared({{ $transaction->id }})"
                            class="mt-2 w-full inline-flex items-center justify-center gap-1 px-2 py-1.5 bg-slate-100 dark:bg-slate-800 rounded-lg font-medium text-xs text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 transition"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            {{ __('Selesai') }}
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Recently prepared -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Sudah Siap') }}</h3>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                {{ $recentlyPrepared->count() }}
            </span>
        </div>

        @if ($recentlyPrepared->isEmpty())
            <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-8 text-center">
                <p class="text-sm text-slate-400 dark:text-slate-500">{{ __('Belum ada pesanan yang siap hari ini.') }}</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5">
                @foreach ($recentlyPrepared as $transaction)
                    <div
                        wire:click="openDetail({{ $transaction->id }})"
                        wire:key="done-{{ $transaction->id }}"
                        class="cursor-pointer relative bg-white dark:bg-slate-900 border-2 border-emerald-400 dark:border-emerald-500 rounded-xl p-3 hover:shadow-md transition"
                    >
                        <span class="absolute -top-2 -right-2 flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500 text-white shadow-sm ring-2 ring-white dark:ring-slate-950">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        </span>
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500 truncate">{{ $transaction->transaction_no }}</span>
                            <span class="shrink-0 text-[10px] text-slate-400 dark:text-slate-500">{{ $transaction->prepared_at->format('H:i') }}</span>
                        </div>
                        @if ($transaction->customer_name)
                            <p class="mt-0.5 text-sm font-semibold text-slate-900 dark:text-slate-100 truncate">{{ $transaction->customer_name }}</p>
                        @endif

                        <ul class="mt-1.5 space-y-1">
                            @foreach ($transaction->items as $item)
                                <li class="text-xs leading-snug text-slate-600 dark:text-slate-300 truncate">
                                    <span class="font-medium text-slate-800 dark:text-slate-100">{{ $item->qty }}x</span> {{ $item->product_name }}
                                    @if ($item->note)
                                        <span class="block text-xs font-medium text-brand-600 dark:text-brand-400">{{ $item->note }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        @if ($transaction->note)
                            <p class="mt-1.5 text-xs font-medium text-amber-700 dark:text-amber-400 truncate">{{ $transaction->note }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Detail Modal -->
    @if ($viewing)
    <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-slate-900/60" wire:click="closeDetail" wire:transition.opacity></div>
        <div wire:transition.scale.origin.top class="relative mb-6 bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-soft sm:max-w-md sm:mx-auto">
            <div class="p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $viewing->transaction_no }}</h3>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                            {{ $viewing->created_at->format('d/m/Y H:i') }} &middot; {{ __('Kasir') }}: {{ $viewing->user->name }}
                        </p>
                    </div>
                    @if ($viewing->isPrepared())
                        <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">{{ __('Sudah Siap') }}</span>
                    @else
                        <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">{{ __('Belum Selesai') }}</span>
                    @endif
                </div>

                @if ($viewing->customer_name)
                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ __('Customer') }}: <span class="font-medium">{{ $viewing->customer_name }}</span></p>
                @endif

                <ul class="mt-4 space-y-2 border-t border-slate-100 dark:border-slate-800 pt-4">
                    @foreach ($viewing->items as $item)
                        <li class="flex items-start justify-between text-sm gap-3">
                            <div>
                                <span class="font-medium text-slate-900 dark:text-slate-100">{{ $item->qty }}x {{ $item->product_name }}</span>
                                @if ($item->note)
                                    <span class="block text-xs text-brand-600 dark:text-brand-400">{{ $item->note }}</span>
                                @endif
                            </div>
                            <span class="shrink-0 text-slate-500 dark:text-slate-400">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                        </li>
                    @endforeach
                </ul>

                @if ($viewing->note)
                    <p class="mt-4 text-xs text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/60 rounded-lg px-3 py-2">
                        {{ __('Catatan') }}: {{ $viewing->note }}
                    </p>
                @endif

                <div class="mt-4 flex justify-between text-base font-semibold border-t border-slate-100 dark:border-slate-800 pt-4">
                    <span class="text-slate-900 dark:text-slate-100">{{ __('Total') }}</span>
                    <span class="text-brand-600 dark:text-brand-400">Rp {{ number_format($viewing->total, 0, ',', '.') }}</span>
                </div>

                <div class="mt-6 flex gap-3">
                    <button type="button" wire:click="closeDetail" class="flex-1 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        {{ __('Tutup') }}
                    </button>
                    @if ($viewing->isPrepared())
                        <button type="button" wire:click="markUnprepared({{ $viewing->id }})" class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-slate-500 rounded-lg hover:bg-slate-600 transition">
                            {{ __('Batalkan Selesai') }}
                        </button>
                    @else
                        <button type="button" wire:click="markPrepared({{ $viewing->id }})" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-200 bg-slate-200 dark:bg-slate-700 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            {{ __('Tandai Selesai') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
