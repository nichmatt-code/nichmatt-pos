<div class="space-y-6">
    <div class="relative w-full sm:w-80">
        <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
        <x-text-input wire:model.live.debounce.300ms="search" type="text" class="w-full pl-10" placeholder="Cari nama toko..." />
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500 bg-slate-50/70 dark:bg-slate-800/40">
                        <th class="px-6 py-3">{{ __('Toko') }}</th>
                        <th class="px-6 py-3">{{ __('Status Akses') }}</th>
                        <th class="px-6 py-3">{{ __('Karyawan') }}</th>
                        <th class="px-6 py-3">{{ __('Produk') }}</th>
                        <th class="px-6 py-3">{{ __('Transaksi') }}</th>
                        <th class="px-6 py-3">{{ __('Omzet') }}</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($stores as $store)
                        <tr wire:key="store-{{ $store->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="px-6 py-3.5">
                                <div class="text-slate-900 dark:text-slate-100 font-medium">{{ $store->name }}</div>
                                <div class="text-xs text-slate-400 dark:text-slate-500">{{ $store->address ?: '—' }}</div>
                            </td>
                            <td class="px-6 py-3.5">
                                @if ($store->subscriptionActive())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                        {{ __('Aktif') }} &middot; {{ __(':days hari lagi', ['days' => $store->accessDaysLeft()]) }}
                                    </span>
                                @elseif ($store->onTrial())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                                        {{ __('Trial') }} &middot; {{ __(':days hari lagi', ['days' => $store->accessDaysLeft()]) }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400">
                                        {{ __('Kedaluwarsa') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400">{{ $store->users_count }}</td>
                            <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400">{{ $productCounts[$store->id] ?? 0 }}</td>
                            <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400">{{ $revenue[$store->id]->tx_count ?? 0 }}</td>
                            <td class="px-6 py-3.5 text-slate-900 dark:text-slate-100">Rp {{ number_format($revenue[$store->id]->tx_revenue ?? 0, 0, ',', '.') }}</td>
                            <td class="px-6 py-3.5 text-right">
                                <a href="{{ route('developer.stores.show', $store) }}" wire:navigate class="text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300 font-medium">{{ __('Detail') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">{{ __('Tidak ada toko ditemukan.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800">
            {{ $stores->links() }}
        </div>
    </div>
</div>
