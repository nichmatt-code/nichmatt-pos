<div>
    <script
        src="{{ $isProduction ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
        data-client-key="{{ $clientKey }}"
    ></script>

    <div class="text-center">
        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 dark:bg-brand-500/10 text-brand-600 dark:text-brand-400">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 2v8m0 0v2m0-2c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </span>

        <h1 class="mt-4 text-xl font-semibold text-slate-900 dark:text-slate-100 tracking-tight">{{ __('Berlangganan NichmattPOS') }}</h1>

        @if ($store->onTrial())
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                {{ __('Masa trial toko :name masih tersisa :days hari.', ['name' => $store->name, 'days' => $store->trialDaysLeft()]) }}
            </p>
        @elseif ($store->subscriptionActive())
            <p class="mt-2 text-sm text-emerald-600 dark:text-emerald-400 font-medium">
                {{ __('Langganan aktif hingga :date.', ['date' => $store->subscription_ends_at->translatedFormat('d F Y')]) }}
            </p>
        @else
            <p class="mt-2 text-sm text-rose-600 dark:text-rose-400 font-medium">
                {{ __('Masa trial toko :name sudah habis. Berlangganan untuk terus menggunakan aplikasi.', ['name' => $store->name]) }}
            </p>
        @endif
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/40 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="font-medium text-slate-900 dark:text-slate-100">{{ __('Langganan Bulanan') }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Akses penuh semua fitur, per toko') }}</p>
            </div>
            <p class="text-xl font-semibold text-brand-600 dark:text-brand-400">Rp {{ number_format(\App\Models\Store::SUBSCRIPTION_MONTHLY_PRICE, 0, ',', '.') }}</p>
        </div>
    </div>

    @if ($latestPayment && $latestPayment->status === 'pending')
        <p class="mt-4 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-100 dark:border-amber-900/60 px-4 py-2.5 text-sm text-amber-700 dark:text-amber-300 text-center">
            {{ __('Ada pembayaran yang masih menunggu konfirmasi.') }}
        </p>
    @endif

    <div class="mt-6 flex flex-col items-center gap-3">
        <x-primary-button
            type="button"
            class="w-full justify-center py-3"
            x-data
            x-on:click="
                $wire.subscribe().then(() => {
                    snap.pay($wire.snapToken, {
                        onSuccess() { window.location.reload() },
                        onPending() { window.location.reload() },
                        onError() { alert('{{ __('Pembayaran gagal, silakan coba lagi.') }}') },
                    })
                })
            "
        >
            {{ __('Bayar dengan Midtrans') }}
        </x-primary-button>

        <button wire:click="logout" class="text-sm text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300">
            {{ __('Keluar') }}
        </button>
    </div>
</div>
