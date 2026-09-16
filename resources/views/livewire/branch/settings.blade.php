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
                <x-primary-button wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">{{ __('Simpan') }}</span>
                    <span wire:loading wire:target="save">{{ __('Menyimpan...') }}</span>
                </x-primary-button>

                <x-action-message class="me-3" on="branch-updated">
                    {{ __('Tersimpan.') }}
                </x-action-message>
            </div>
        </form>
    </div>

    <div class="mt-6 bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6 sm:p-8">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Logo Toko') }}</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Ditampilkan di bagian atas bill, struk, dan invoice.') }}</p>

        <form wire:submit="saveLogo" class="mt-4 space-y-4">
            <div class="flex items-center gap-4">
                @if ($logo)
                    <img src="{{ $logo->temporaryUrl() }}" class="h-16 w-16 rounded-lg object-contain border border-slate-200 dark:border-slate-700 bg-white">
                @elseif ($existingLogoUrl)
                    <img src="{{ $existingLogoUrl }}" class="h-16 w-16 rounded-lg object-contain border border-slate-200 dark:border-slate-700 bg-white">
                @else
                    <span class="flex h-16 w-16 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </span>
                @endif

                <div class="flex-1">
                    <input wire:model="logo" type="file" accept="image/*" class="block w-full text-sm text-slate-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-brand-500/10 dark:file:text-brand-300 dark:hover:file:bg-brand-500/20" />
                    <div wire:loading wire:target="logo" class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ __('Mengunggah...') }}</div>
                    @if ($existingLogoUrl || $logo)
                        <button type="button" wire:click="removeLogo" class="mt-1 text-xs text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300">{{ __('Hapus logo') }}</button>
                    @endif
                </div>
            </div>
            <x-input-error :messages="$errors->get('logo')" class="mt-2" />

            <div class="flex items-center gap-4">
                <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="saveLogo,logo">
                    <span wire:loading.remove wire:target="saveLogo">{{ __('Simpan Logo') }}</span>
                    <span wire:loading wire:target="saveLogo">{{ __('Menyimpan...') }}</span>
                </x-primary-button>

                <x-action-message class="me-3" on="logo-updated">
                    {{ __('Tersimpan.') }}
                </x-action-message>
            </div>
        </form>
    </div>

    <div class="mt-6 bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6 sm:p-8">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Format Struk') }}</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Pilih tata letak struk saat tombol "Cetak Struk" ditekan di kasir maupun laporan.') }}</p>

        <form wire:submit="saveReceiptFormat" class="mt-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="flex items-start gap-3 rounded-2xl border p-4 cursor-pointer transition {{ $receiptFormat === 'thermal' ? 'border-brand-400 bg-brand-50/60 dark:border-brand-600 dark:bg-brand-500/10' : 'border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/40 hover:border-slate-300 dark:hover:border-slate-600' }}">
                    <input type="radio" wire:model="receiptFormat" value="thermal" class="mt-1 text-brand-600 focus:ring-brand-500">
                    <span>
                        <span class="block font-medium text-slate-900 dark:text-slate-100">{{ __('Printer Thermal') }}</span>
                        <span class="block text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ __('Ukuran kertas kecil (80mm), cocok untuk printer thermal via USB/Bluetooth.') }}</span>
                    </span>
                </label>

                <label class="flex items-start gap-3 rounded-2xl border p-4 cursor-pointer transition {{ $receiptFormat === 'pdf' ? 'border-brand-400 bg-brand-50/60 dark:border-brand-600 dark:bg-brand-500/10' : 'border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/40 hover:border-slate-300 dark:hover:border-slate-600' }}">
                    <input type="radio" wire:model="receiptFormat" value="pdf" class="mt-1 text-brand-600 focus:ring-brand-500">
                    <span>
                        <span class="block font-medium text-slate-900 dark:text-slate-100">{{ __('Invoice / PDF') }}</span>
                        <span class="block text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ __('Tata letak ukuran kertas biasa, cocok untuk disimpan sebagai PDF atau dicetak dari HP.') }}</span>
                    </span>
                </label>
            </div>

            <div class="flex items-center gap-4 pt-4">
                <x-primary-button wire:loading.attr="disabled" wire:target="saveReceiptFormat">
                    <span wire:loading.remove wire:target="saveReceiptFormat">{{ __('Simpan') }}</span>
                    <span wire:loading wire:target="saveReceiptFormat">{{ __('Menyimpan...') }}</span>
                </x-primary-button>

                <x-action-message class="me-3" on="receipt-format-updated">
                    {{ __('Tersimpan.') }}
                </x-action-message>
            </div>
        </form>
    </div>

    <div class="mt-6 bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6 sm:p-8">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Pengaturan Kasir') }}</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Atur tampilan dan perilaku halaman Kasir.') }}</p>

        <form wire:submit="savePosSettings" class="mt-4 space-y-4">
            <label class="flex items-start gap-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/40 p-4 cursor-pointer">
                <input type="checkbox" wire:model="showProductImages" class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-600 dark:bg-slate-800">
                <span>
                    <span class="block font-medium text-slate-900 dark:text-slate-100">{{ __('Tampilkan gambar produk') }}</span>
                    <span class="block text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ __('Matikan untuk tampilan daftar produk yang lebih ringkas dan cepat di layar Kasir.') }}</span>
                </span>
            </label>

            <label class="flex items-start gap-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/40 p-4 cursor-pointer">
                <input type="checkbox" wire:model="allowPriceEdit" class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-600 dark:bg-slate-800">
                <span>
                    <span class="block font-medium text-slate-900 dark:text-slate-100">{{ __('Kasir bisa mengubah harga saat transaksi') }}</span>
                    <span class="block text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ __('Bila aktif, harga tiap item di keranjang bisa diedit langsung oleh kasir sebelum pembayaran.') }}</span>
                </span>
            </label>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="taxPercent" value="Pajak (%)" />
                    <x-text-input wire:model="taxPercent" id="taxPercent" type="number" min="0" max="100" class="block w-full" />
                    <x-input-error :messages="$errors->get('taxPercent')" class="mt-1" />
                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ __('mis. 11 untuk PPN 11%. Isi 0 bila tidak ada pajak.') }}</p>
                </div>
                <div>
                    <x-input-label for="serviceChargePercent" value="Service Charge (%)" />
                    <x-text-input wire:model="serviceChargePercent" id="serviceChargePercent" type="number" min="0" max="100" class="block w-full" />
                    <x-input-error :messages="$errors->get('serviceChargePercent')" class="mt-1" />
                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ __('Isi 0 bila tidak ada service charge.') }}</p>
                </div>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <x-primary-button wire:loading.attr="disabled" wire:target="savePosSettings">
                    <span wire:loading.remove wire:target="savePosSettings">{{ __('Simpan') }}</span>
                    <span wire:loading wire:target="savePosSettings">{{ __('Menyimpan...') }}</span>
                </x-primary-button>

                <x-action-message class="me-3" on="pos-settings-updated">
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
            <a href="{{ route('self-order.qr') }}" target="_blank" class="shrink-0 inline-flex items-center justify-center px-4 py-2.5 bg-brand-600 rounded-lg font-medium text-sm text-white shadow-sm hover:bg-brand-700 transition">
                {{ __('Lihat QR') }}
            </a>
        </div>
    </div>
</div>
