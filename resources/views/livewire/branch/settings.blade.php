<div class="max-w-2xl">
    <div class="mb-6 bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6 sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Status Langganan') }}</h3>

                @if ($store->subscriptionActive())
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ __('Aktif sampai :date (:days hari lagi).', ['date' => $store->subscription_ends_at->translatedFormat('d F Y'), 'days' => $store->accessDaysLeft()]) }}
                    </p>
                @elseif ($store->onTrial())
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ __('Masa trial sampai :date (:days hari lagi).', ['date' => $store->trial_ends_at->translatedFormat('d F Y'), 'days' => $store->accessDaysLeft()]) }}
                    </p>
                @else
                    <p class="mt-1 text-sm text-rose-600 dark:text-rose-400 font-medium">{{ __('Masa aktif sudah habis.') }}</p>
                @endif
            </div>

            @if ($store->subscriptionActive())
                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">{{ __('Aktif') }}</span>
            @elseif ($store->onTrial())
                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-semibold bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">{{ __('Trial') }}</span>
            @else
                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-semibold bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400">{{ __('Kedaluwarsa') }}</span>
            @endif
        </div>

        <a href="{{ route('billing.subscribe') }}" wire:navigate class="mt-4 inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 rounded-lg font-medium text-sm text-white shadow-sm hover:bg-brand-700 transition">
            {{ __('Perpanjang Langganan') }}
        </a>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6 sm:p-8">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Informasi Cabang') }}</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Detail ini muncul pada struk transaksi.') }}</p>

        <form wire:submit="save" class="mt-6 space-y-4">
            <div>
                <x-input-label for="name" value="Nama Toko / Cabang" />
                <x-text-input wire:model="name" id="name" type="text" class="block w-full" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="address" value="Alamat" />
                <textarea wire:model="address" id="address" rows="3" class="mt-1 block w-full border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100"></textarea>
                <x-input-error :messages="$errors->get('address')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="phone" value="No. Telepon" />
                <x-text-input wire:model="phone" id="phone" type="text" class="block w-full" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>

            <div class="flex items-center gap-4 pt-2">
                <x-primary-button>{{ __('Simpan') }}</x-primary-button>

                <x-action-message class="me-3" on="branch-updated">
                    {{ __('Tersimpan.') }}
                </x-action-message>
            </div>
        </form>
    </div>

    <div class="mt-6 bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6 sm:p-8" x-data>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Link Self Order') }}</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Bagikan link ini ke pelanggan (misalnya lewat kode QR di meja) agar mereka bisa pesan sendiri.') }}</p>

        <div class="mt-4 flex flex-col sm:flex-row gap-2">
            <input type="text" readonly value="{{ $store->selfOrderUrl() }}" onclick="this.select()" class="flex-1 text-sm border-slate-200 bg-slate-50/60 rounded-lg text-slate-700 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-300">
            <button type="button" x-on:click="navigator.clipboard.writeText('{{ $store->selfOrderUrl() }}')" class="shrink-0 inline-flex items-center justify-center px-4 py-2.5 bg-white border border-slate-200 rounded-lg font-medium text-sm text-slate-700 shadow-sm hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-700">
                {{ __('Salin Link') }}
            </button>
        </div>
    </div>
</div>
