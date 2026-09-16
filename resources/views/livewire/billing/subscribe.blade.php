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

        @if ($store->subscriptionActive())
            <p class="mt-2 text-sm text-emerald-600 dark:text-emerald-400 font-medium">
                {{ __('Langganan aktif hingga :date.', ['date' => $store->subscription_ends_at->translatedFormat('d F Y')]) }}
            </p>
        @elseif ($store->onTrial())
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                {{ __('Masa trial toko :name masih tersisa :days hari. Anda bisa berlangganan kapan saja, tidak perlu menunggu trial habis.', ['name' => $store->name, 'days' => $store->trialDaysLeft()]) }}
            </p>
        @else
            <p class="mt-2 text-sm text-rose-600 dark:text-rose-400 font-medium">
                {{ __('Masa trial toko :name sudah habis. Berlangganan untuk terus menggunakan aplikasi.', ['name' => $store->name]) }}
            </p>
        @endif
    </div>

    <div class="mt-6 grid grid-cols-1 {{ $plans->count() > 1 ? 'sm:grid-cols-2' : '' }} gap-3">
        @foreach ($plans as $plan)
            <button
                type="button"
                wire:click="selectPlan({{ $plan->id }})"
                class="text-left rounded-2xl border p-4 transition {{ $selectedPlanId === $plan->id ? 'border-brand-400 bg-brand-50/60 dark:border-brand-600 dark:bg-brand-500/10' : 'border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/40 hover:border-slate-300 dark:hover:border-slate-600' }}"
            >
                <p class="font-medium text-slate-900 dark:text-slate-100">{{ $plan->name }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __(':days hari', ['days' => $plan->duration_days]) }}</p>
                @if ($plan->description)
                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ $plan->description }}</p>
                @endif

                <div class="mt-2">
                    @if ($plan->hasActivePromo())
                        <span class="text-xs text-slate-400 dark:text-slate-500 line-through">Rp {{ number_format($plan->price, 0, ',', '.') }}</span>
                        <p class="text-lg font-semibold text-brand-600 dark:text-brand-400">Rp {{ number_format($plan->promo_price, 0, ',', '.') }}</p>
                        @if ($plan->promo_label)
                            <span class="inline-flex mt-1 items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400">{{ $plan->promo_label }}</span>
                        @endif
                    @else
                        <p class="text-lg font-semibold text-brand-600 dark:text-brand-400">Rp {{ number_format($plan->price, 0, ',', '.') }}</p>
                    @endif
                </div>
            </button>
        @endforeach
    </div>

    @if ($this->selectedPlan && $this->selectedPlan->featureList())
        <div class="mt-4 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ __('Fitur yang didapat') }}</p>
            <ul class="mt-2 space-y-1.5 text-sm text-slate-600 dark:text-slate-300">
                @foreach ($this->selectedPlan->featureList() as $feature)
                    <li class="flex items-start gap-2">
                        <svg class="h-5 w-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        {{ $feature }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-4">
        <x-input-label for="promoCodeInput" value="Kode Promo (opsional)" />
        <div class="mt-1.5 flex gap-2">
            @if ($this->appliedPromoCode)
                <div class="flex-1 flex items-center justify-between px-3 py-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-100 dark:border-emerald-900/60">
                    <span class="text-sm font-medium text-emerald-700 dark:text-emerald-400">{{ $this->appliedPromoCode->code }} {{ __('diterapkan') }}</span>
                    <button type="button" wire:click="removePromoCode" class="text-xs text-emerald-600 dark:text-emerald-400 underline">{{ __('Hapus') }}</button>
                </div>
            @else
                <x-text-input wire:model="promoCodeInput" id="promoCodeInput" type="text" class="block w-full uppercase" placeholder="mis. HEMAT10" />
                <x-secondary-button type="button" wire:click="applyPromoCode" class="shrink-0">{{ __('Terapkan') }}</x-secondary-button>
            @endif
        </div>
        <x-input-error :messages="$errors->get('promoCodeInput')" class="mt-2" />
    </div>

    <div class="mt-4 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/40 p-5">
        <div class="flex items-center justify-between">
            <p class="font-medium text-slate-900 dark:text-slate-100">{{ __('Total Bayar') }}</p>
            <p class="text-xl font-semibold text-brand-600 dark:text-brand-400">Rp {{ number_format($this->finalPrice, 0, ',', '.') }}</p>
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
                    if (! $wire.snapToken) { return }
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
