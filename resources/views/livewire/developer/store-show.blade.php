<div class="space-y-6">
    <a href="{{ route('developer.stores.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
        {{ __('Kembali ke daftar toko') }}
    </a>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $store->name }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ $store->address ?: __('Tanpa alamat') }} @if ($store->phone) &middot; {{ $store->phone }} @endif</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ __('Terdaftar') }} {{ $store->created_at->translatedFormat('d F Y') }}</p>
            </div>

            @if ($store->subscriptionActive())
                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                    {{ __('Aktif — :days hari lagi', ['days' => $store->accessDaysLeft()]) }}
                </span>
            @elseif ($store->onTrial())
                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-semibold bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                    {{ __('Trial — :days hari lagi', ['days' => $store->accessDaysLeft()]) }}
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-semibold bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400">
                    {{ __('Kedaluwarsa') }}
                </span>
            @endif
        </div>

        <dl class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm border-t border-slate-100 dark:border-slate-800 pt-4">
            <div>
                <dt class="text-slate-400 dark:text-slate-500">{{ __('Trial berakhir') }}</dt>
                <dd class="text-slate-900 dark:text-slate-100 font-medium">{{ $store->trial_ends_at?->translatedFormat('d M Y') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-slate-400 dark:text-slate-500">{{ __('Langganan berakhir') }}</dt>
                <dd class="text-slate-900 dark:text-slate-100 font-medium">{{ $store->subscription_ends_at?->translatedFormat('d M Y') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-slate-400 dark:text-slate-500">{{ __('Link Self Order') }}</dt>
                <dd class="text-brand-600 dark:text-brand-400 font-medium truncate">{{ $store->selfOrderUrl() }}</dd>
            </div>
        </dl>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6">
        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Atur Masa Aktif Manual') }}</h3>
        <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ __('Berikan akses ke toko ini tanpa perlu berlangganan lewat Midtrans.') }}</p>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <form wire:submit="extend" class="flex items-end gap-2">
                <div class="flex-1">
                    <x-input-label for="extendDays" value="Tambah Hari" />
                    <x-text-input wire:model="extendDays" id="extendDays" type="number" min="1" class="block w-full mt-1" placeholder="mis. 30" />
                    <x-input-error :messages="$errors->get('extendDays')" class="mt-1" />
                </div>
                <x-secondary-button type="submit" class="shrink-0">{{ __('Tambah') }}</x-secondary-button>
            </form>

            <form wire:submit="setExpiry" class="flex items-end gap-2">
                <div class="flex-1">
                    <x-input-label for="customExpiryDate" value="Set Tanggal Berakhir" />
                    <input wire:model="customExpiryDate" id="customExpiryDate" type="date" class="block w-full mt-1 border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100" />
                    <x-input-error :messages="$errors->get('customExpiryDate')" class="mt-1" />
                </div>
                <x-secondary-button type="submit" class="shrink-0">{{ __('Set') }}</x-secondary-button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-5">
            <div class="text-xs font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wide">{{ __('Produk') }}</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $stats['products'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-5">
            <div class="text-xs font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wide">{{ __('Kategori') }}</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $stats['categories'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-5">
            <div class="text-xs font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wide">{{ __('Transaksi') }}</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $stats['transactions'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-5">
            <div class="text-xs font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wide">{{ __('Omzet') }}</div>
            <div class="mt-1 text-2xl font-semibold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($stats['revenue'], 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Users -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Karyawan') }}</h3>
            </div>
            <table class="min-w-full text-sm">
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($users as $user)
                        <tr>
                            <td class="px-6 py-3">
                                <div class="text-slate-900 dark:text-slate-100 font-medium">{{ $user->name }}</div>
                                <div class="text-xs text-slate-400 dark:text-slate-500">{{ $user->email }}</div>
                            </td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $user->isOwner() ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                    {{ $user->isOwner() ? 'Owner' : 'Karyawan' }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-right">
                                @if ($user->is_active)
                                    <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">{{ __('Aktif') }}</span>
                                @else
                                    <span class="text-xs text-rose-600 dark:text-rose-400 font-medium">{{ __('Nonaktif') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Subscription payments -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Riwayat Pembayaran Langganan') }}</h3>
            </div>
            <table class="min-w-full text-sm">
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="px-6 py-3">
                                <div class="text-slate-900 dark:text-slate-100 font-medium">{{ $payment->order_id }}</div>
                                <div class="text-xs text-slate-400 dark:text-slate-500">{{ $payment->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="px-6 py-3 text-slate-500 dark:text-slate-400">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                            <td class="px-6 py-3 text-right">
                                <span class="text-xs font-medium {{ $payment->status === 'settlement' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}">
                                    {{ ucfirst($payment->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('Belum ada pembayaran.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent transactions -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Transaksi Terbaru') }}</h3>
        </div>
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">
                    <th class="px-6 py-2">{{ __('No. Transaksi') }}</th>
                    <th class="px-6 py-2">{{ __('Waktu') }}</th>
                    <th class="px-6 py-2">{{ __('Status') }}</th>
                    <th class="px-6 py-2">{{ __('Total') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($recentTransactions as $transaction)
                    <tr>
                        <td class="px-6 py-2.5 text-slate-900 dark:text-slate-100">{{ $transaction->transaction_no }}</td>
                        <td class="px-6 py-2.5 text-slate-500 dark:text-slate-400">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-2.5 text-slate-500 dark:text-slate-400">{{ ucfirst($transaction->status) }}</td>
                        <td class="px-6 py-2.5 text-slate-900 dark:text-slate-100">Rp {{ number_format($transaction->total, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500">{{ __('Belum ada transaksi.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
