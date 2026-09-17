<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="relative w-full sm:w-72">
            <svg wire:loading.remove wire:target="search" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <svg wire:loading wire:target="search" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-brand-500 dark:text-brand-400 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <x-text-input wire:model.live.debounce.300ms="search" type="text" class="w-full pl-10" placeholder="Cari nama atau nomor HP..." />
        </div>
        <x-primary-button wire:click="createCustomer">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('Tambah Pelanggan') }}
        </x-primary-button>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500 bg-slate-50/70 dark:bg-slate-800/40">
                        <x-th-sort field="name" label="{{ __('Nama') }}" :sortField="$sortField" :sortDirection="$sortDirection" />
                        <th class="px-6 py-3">{{ __('No. HP') }}</th>
                        <th class="px-6 py-3">{{ __('Alamat') }}</th>
                        <th class="px-6 py-3">{{ __('Tgl. Lahir') }}</th>
                        <x-th-sort field="transactions_count" label="{{ __('Total Transaksi') }}" :sortField="$sortField" :sortDirection="$sortDirection" />
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($customers as $customer)
                        <tr wire:key="customer-{{ $customer->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="px-6 py-3.5 text-slate-900 dark:text-slate-100 font-medium">{{ $customer->name }}</td>
                            <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400">{{ $customer->phone }}</td>
                            <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400 max-w-xs truncate">{{ $customer->address ?? '-' }}</td>
                            <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                @if ($customer->birthdate)
                                    {{ $customer->birthdate->translatedFormat('d M Y') }}
                                    <span class="text-xs text-slate-400 dark:text-slate-500">({{ $customer->age() }} th)</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400">{{ $customer->transactions_count }}</td>
                            <td class="px-6 py-3.5 text-right space-x-3 whitespace-nowrap">
                                <button wire:click="editCustomer({{ $customer->id }})" class="text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300 font-medium">{{ __('Edit') }}</button>
                                <button wire:click="delete({{ $customer->id }})" wire:confirm="Hapus pelanggan ini?" class="text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300 font-medium">{{ __('Hapus') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">{{ __('Belum ada pelanggan.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800">
            {{ $customers->links() }}
        </div>
    </div>

    <!-- Customer Form Modal -->
    @if ($showFormModal)
    <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-slate-900/60" wire:click="$set('showFormModal', false)"></div>
        <div class="relative mb-6 bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-soft sm:max-w-md sm:mx-auto">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $editingId ? __('Edit Pelanggan') : __('Tambah Pelanggan') }}</h3>

            <form wire:submit="save" class="mt-4 space-y-4">
                <div>
                    <x-input-label for="name" value="Nama" />
                    <x-text-input wire:model="name" id="name" type="text" class="block w-full" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="phone" value="Nomor HP" />
                    <x-text-input wire:model="phone" id="phone" type="text" class="block w-full" placeholder="mis. 081234567890" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="address" value="Alamat (opsional)" />
                    <textarea wire:model="address" id="address" rows="2" class="mt-1 block w-full border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100"></textarea>
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="birthdate" value="Tanggal Lahir (opsional)" />
                    <input wire:model="birthdate" id="birthdate" type="date" class="mt-1 block w-full border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100">
                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ __('Dipakai untuk kupon promo ulang tahun yang nilainya mengikuti umur.') }}</p>
                    <x-input-error :messages="$errors->get('birthdate')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="notes" value="Catatan (opsional)" />
                    <textarea wire:model="notes" id="notes" rows="2" placeholder="mis. alergi kacang" class="mt-1 block w-full border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100"></textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="$set('showFormModal', false)" class="px-4 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">{{ __('Batal') }}</button>
                    <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? __('Update') : __('Tambah') }}</span>
                        <span wire:loading wire:target="save">{{ __('Menyimpan...') }}</span>
                    </x-primary-button>
                </div>
            </form>
        </div>
        </div>
    </div>
    @endif
</div>
