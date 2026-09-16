<div class="space-y-8">
    <!-- Plans -->
    <div>
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Paket Langganan') }}</h3>
            <x-primary-button wire:click="createPlan">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                {{ __('Tambah Paket') }}
            </x-primary-button>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500 bg-slate-50/70 dark:bg-slate-800/40">
                        <th class="px-6 py-3">{{ __('Paket') }}</th>
                        <th class="px-6 py-3">{{ __('Durasi') }}</th>
                        <th class="px-6 py-3">{{ __('Harga') }}</th>
                        <th class="px-6 py-3">{{ __('Promo') }}</th>
                        <th class="px-6 py-3">{{ __('Status') }}</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($plans as $plan)
                        <tr wire:key="plan-{{ $plan->id }}">
                            <td class="px-6 py-3.5">
                                <div class="text-slate-900 dark:text-slate-100 font-medium">{{ $plan->name }}</div>
                                <div class="text-xs text-slate-400 dark:text-slate-500">{{ $plan->code }}</div>
                                @if ($plan->description)
                                    <div class="text-xs text-slate-400 dark:text-slate-500 mt-0.5 max-w-xs truncate">{{ $plan->description }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400">{{ $plan->duration_days }} {{ __('hari') }}</td>
                            <td class="px-6 py-3.5 text-slate-900 dark:text-slate-100">Rp {{ number_format($plan->price, 0, ',', '.') }}</td>
                            <td class="px-6 py-3.5">
                                @if ($plan->hasActivePromo())
                                    <span class="text-emerald-600 dark:text-emerald-400 font-medium">Rp {{ number_format($plan->promo_price, 0, ',', '.') }}</span>
                                    @if ($plan->promo_label)
                                        <div class="text-xs text-slate-400 dark:text-slate-500">{{ $plan->promo_label }}</div>
                                    @endif
                                @else
                                    <span class="text-slate-300 dark:text-slate-600">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5">
                                @if ($plan->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">{{ __('Aktif') }}</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ __('Nonaktif') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-right space-x-3 whitespace-nowrap">
                                <button wire:click="editPlan({{ $plan->id }})" class="text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300 font-medium">{{ __('Edit') }}</button>
                                <button wire:click="deletePlan({{ $plan->id }})" wire:confirm="Hapus paket ini?" class="text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300 font-medium">{{ __('Hapus') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">{{ __('Belum ada paket.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Promo codes -->
    <div>
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Kode Promo') }}</h3>
            <x-primary-button wire:click="createPromo">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                {{ __('Tambah Kode Promo') }}
            </x-primary-button>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500 bg-slate-50/70 dark:bg-slate-800/40">
                        <th class="px-6 py-3">{{ __('Kode') }}</th>
                        <th class="px-6 py-3">{{ __('Diskon') }}</th>
                        <th class="px-6 py-3">{{ __('Terpakai') }}</th>
                        <th class="px-6 py-3">{{ __('Berlaku Sampai') }}</th>
                        <th class="px-6 py-3">{{ __('Status') }}</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($promoCodes as $promo)
                        <tr wire:key="promo-{{ $promo->id }}">
                            <td class="px-6 py-3.5 text-slate-900 dark:text-slate-100 font-medium">{{ $promo->code }}</td>
                            <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400">
                                {{ $promo->type === 'percent' ? $promo->value.'%' : 'Rp '.number_format($promo->value, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400">{{ $promo->times_redeemed }}{{ $promo->max_redemptions ? ' / '.$promo->max_redemptions : '' }}</td>
                            <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400">{{ $promo->expires_at?->translatedFormat('d M Y') ?? __('Tanpa batas') }}</td>
                            <td class="px-6 py-3.5">
                                @if ($promo->isValid())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">{{ __('Berlaku') }}</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ __('Tidak Berlaku') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-right space-x-3 whitespace-nowrap">
                                <button wire:click="editPromo({{ $promo->id }})" class="text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300 font-medium">{{ __('Edit') }}</button>
                                <button wire:click="deletePromo({{ $promo->id }})" wire:confirm="Hapus kode promo ini?" class="text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300 font-medium">{{ __('Hapus') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">{{ __('Belum ada kode promo.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Plan Form Modal -->
    @if ($showPlanModal)
    <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-slate-900/60" wire:click="$set('showPlanModal', false)" wire:transition.opacity></div>
        <div wire:transition.scale.origin.top class="relative mb-6 bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-soft sm:max-w-lg sm:mx-auto">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $editingPlanId ? __('Edit Paket') : __('Tambah Paket') }}</h3>

            <form wire:submit="savePlan" class="mt-4 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="planName" value="Nama Paket" />
                        <x-text-input wire:model="planName" id="planName" type="text" class="block w-full" placeholder="Langganan Mingguan" />
                        <x-input-error :messages="$errors->get('planName')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="planCode" value="Kode" />
                        <x-text-input wire:model="planCode" id="planCode" type="text" class="block w-full" placeholder="weekly" />
                        <x-input-error :messages="$errors->get('planCode')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="planDescription" value="Deskripsi Singkat (opsional)" />
                    <textarea wire:model="planDescription" id="planDescription" rows="2" class="mt-1 block w-full border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100" placeholder="mis. Cocok untuk mencoba dulu sebelum berlangganan lebih lama."></textarea>
                    <x-input-error :messages="$errors->get('planDescription')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="planFeatures" value="Fitur yang Didapat (opsional, satu baris satu fitur)" />
                    <textarea wire:model="planFeatures" id="planFeatures" rows="5" class="mt-1 block w-full border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100" placeholder="Kasir & Self-Order tanpa batas transaksi&#10;Inventory & Stock Opname&#10;Laporan penjualan real-time"></textarea>
                    <x-input-error :messages="$errors->get('planFeatures')" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="planDurationDays" value="Durasi (hari)" />
                        <x-text-input wire:model="planDurationDays" id="planDurationDays" type="number" class="block w-full" />
                        <x-input-error :messages="$errors->get('planDurationDays')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="planPrice" value="Harga (Rp)" />
                        <x-text-input wire:model="planPrice" id="planPrice" type="number" class="block w-full" />
                        <x-input-error :messages="$errors->get('planPrice')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="planPromoPrice" value="Harga Promo (opsional)" />
                        <x-text-input wire:model="planPromoPrice" id="planPromoPrice" type="number" class="block w-full" />
                        <x-input-error :messages="$errors->get('planPromoPrice')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="planPromoLabel" value="Label Promo (opsional)" />
                        <x-text-input wire:model="planPromoLabel" id="planPromoLabel" type="text" class="block w-full" placeholder="Promo Peluncuran" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="planPromoEndsAt" value="Promo Berakhir (opsional)" />
                        <input wire:model="planPromoEndsAt" id="planPromoEndsAt" type="datetime-local" class="block w-full mt-1 border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100" />
                        <x-input-error :messages="$errors->get('planPromoEndsAt')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="planSortOrder" value="Urutan Tampil" />
                        <x-text-input wire:model="planSortOrder" id="planSortOrder" type="number" class="block w-full" />
                    </div>
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                    <input type="checkbox" wire:model="planIsActive" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-600 dark:bg-slate-800">
                    {{ __('Aktif (ditampilkan ke pelanggan)') }}
                </label>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="$set('showPlanModal', false)" class="px-4 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">{{ __('Batal') }}</button>
                    <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="savePlan">
                        <span wire:loading.remove wire:target="savePlan">{{ __('Simpan') }}</span>
                        <span wire:loading wire:target="savePlan">{{ __('Menyimpan...') }}</span>
                    </x-primary-button>
                </div>
            </form>
        </div>
        </div>
    </div>
    @endif

    <!-- Promo Form Modal -->
    @if ($showPromoModal)
    <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-slate-900/60" wire:click="$set('showPromoModal', false)" wire:transition.opacity></div>
        <div wire:transition.scale.origin.top class="relative mb-6 bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-soft sm:max-w-lg sm:mx-auto">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $editingPromoId ? __('Edit Kode Promo') : __('Tambah Kode Promo') }}</h3>

            <form wire:submit="savePromo" class="mt-4 space-y-4">
                <div>
                    <x-input-label for="promoCode" value="Kode" />
                    <x-text-input wire:model="promoCode" id="promoCode" type="text" class="block w-full uppercase" placeholder="HEMAT10" />
                    <x-input-error :messages="$errors->get('promoCode')" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="promoType" value="Jenis Diskon" />
                        <select wire:model="promoType" id="promoType" class="block w-full mt-1 border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100">
                            <option value="fixed">{{ __('Potongan Rupiah') }}</option>
                            <option value="percent">{{ __('Persentase (%)') }}</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="promoValue" value="Nilai" />
                        <x-text-input wire:model="promoValue" id="promoValue" type="number" class="block w-full" placeholder="{{ $promoType === 'percent' ? '10' : '10000' }}" />
                        <x-input-error :messages="$errors->get('promoValue')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="promoMaxRedemptions" value="Maks. Pemakaian (opsional)" />
                        <x-text-input wire:model="promoMaxRedemptions" id="promoMaxRedemptions" type="number" class="block w-full" placeholder="{{ __('Tanpa batas') }}" />
                        <x-input-error :messages="$errors->get('promoMaxRedemptions')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="promoExpiresAt" value="Berlaku Sampai (opsional)" />
                        <input wire:model="promoExpiresAt" id="promoExpiresAt" type="datetime-local" class="block w-full mt-1 border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100" />
                        <x-input-error :messages="$errors->get('promoExpiresAt')" class="mt-2" />
                    </div>
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                    <input type="checkbox" wire:model="promoIsActive" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-600 dark:bg-slate-800">
                    {{ __('Aktif') }}
                </label>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="$set('showPromoModal', false)" class="px-4 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">{{ __('Batal') }}</button>
                    <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="savePromo">
                        <span wire:loading.remove wire:target="savePromo">{{ __('Simpan') }}</span>
                        <span wire:loading wire:target="savePromo">{{ __('Menyimpan...') }}</span>
                    </x-primary-button>
                </div>
            </form>
        </div>
        </div>
    </div>
    @endif
</div>
