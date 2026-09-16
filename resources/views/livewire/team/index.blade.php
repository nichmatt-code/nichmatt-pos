<div class="space-y-6">
    @if ($lastInviteUrl)
        <div class="rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/60 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-300 flex flex-wrap items-center justify-between gap-2" x-data>
            <span>{{ __('Undangan terkirim. Kalau email belum masuk (server dev belum kirim email sungguhan), salin link ini:') }}</span>
            <div class="flex items-center gap-2">
                <input type="text" readonly value="{{ $lastInviteUrl }}" onclick="this.select()" class="text-xs border-emerald-200 dark:border-emerald-800 bg-white dark:bg-slate-900 dark:text-slate-200 rounded-lg w-64">
                <button type="button" x-on:click="navigator.clipboard.writeText('{{ $lastInviteUrl }}')" class="text-xs font-medium text-emerald-700 hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300 underline">{{ __('Salin') }}</button>
                <button type="button" wire:click="$set('lastInviteUrl', null)" class="text-xs text-emerald-700 hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300">&times;</button>
            </div>
        </div>
    @endif

    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Karyawan') }}</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Kelola siapa yang bisa akses toko Anda dan izin masing-masing.') }}</p>
        </div>
        <x-primary-button wire:click="openInviteModal">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('Undang Karyawan') }}
        </x-primary-button>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500 bg-slate-50/70 dark:bg-slate-800/40">
                    <th class="px-6 py-3">{{ __('Nama') }}</th>
                    <th class="px-6 py-3">{{ __('Role') }}</th>
                    <th class="px-6 py-3">{{ __('Izin Akses') }}</th>
                    <th class="px-6 py-3">{{ __('Status') }}</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($employees as $employee)
                    <tr wire:key="employee-{{ $employee->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                        <td class="px-6 py-3.5">
                            <div class="text-slate-900 dark:text-slate-100 font-medium">{{ $employee->name }}</div>
                            <div class="text-xs text-slate-400 dark:text-slate-500">{{ $employee->email }}</div>
                        </td>
                        <td class="px-6 py-3.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $employee->isOwner() ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                {{ $employee->isOwner() ? 'Owner' : 'Karyawan' }}
                            </span>
                        </td>
                        <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400">
                            @if ($employee->isOwner())
                                <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('Akses penuh') }}</span>
                            @elseif (empty($employee->permissions))
                                <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('Kasir saja') }}</span>
                            @else
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($permissions as $permission)
                                        @if (in_array($permission->value, $employee->permissions ?? [], true))
                                            <span class="inline-flex px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-xs text-slate-600 dark:text-slate-300">{{ $permission->label() }}</span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-3.5">
                            @if ($employee->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">{{ __('Aktif') }}</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400">{{ __('Nonaktif') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-3.5 text-right">
                            @if ($employee->id === auth()->id())
                                <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('Anda') }}</span>
                            @else
                                <button wire:click="openEditModal({{ $employee->id }})" class="text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300 font-medium text-sm">{{ __('Detail') }}</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($pendingInvitations->isNotEmpty())
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Undangan Menunggu') }}</h3>
            </div>
            <table class="min-w-full text-sm">
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($pendingInvitations as $invitation)
                        <tr wire:key="invitation-{{ $invitation->id }}">
                            <td class="px-6 py-3 text-slate-900 dark:text-slate-100">{{ $invitation->email }}</td>
                            <td class="px-6 py-3 text-slate-500 dark:text-slate-400">{{ $invitation->role === 'owner' ? 'Owner' : 'Karyawan' }}</td>
                            <td class="px-6 py-3 text-xs text-slate-400 dark:text-slate-500">
                                @if ($invitation->isExpired())
                                    {{ __('Kedaluwarsa') }}
                                @else
                                    {{ __('Berlaku sampai :date', ['date' => $invitation->expires_at->translatedFormat('d M Y')]) }}
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right space-x-3 whitespace-nowrap">
                                <button wire:click="resendInvite({{ $invitation->id }})" class="text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300 font-medium">{{ __('Kirim Ulang') }}</button>
                                <button wire:click="cancelInvite({{ $invitation->id }})" wire:confirm="Batalkan undangan ini?" class="text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300 font-medium">{{ __('Batalkan') }}</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Invite Modal -->
    @if ($showInviteModal)
    <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-slate-900/60" wire:click="$set('showInviteModal', false)"></div>
        <div class="relative mb-6 bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-soft sm:max-w-md sm:mx-auto">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Undang Karyawan') }}</h3>

                <form wire:submit="sendInvite" class="mt-4 space-y-4">
                    <div>
                        <x-input-label for="inviteEmail" value="Email" />
                        <x-text-input wire:model="inviteEmail" id="inviteEmail" type="email" class="block w-full" />
                        <x-input-error :messages="$errors->get('inviteEmail')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="inviteRole" value="Role" />
                        <select wire:model.live="inviteRole" id="inviteRole" class="block w-full mt-1 border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100">
                            <option value="kasir">{{ __('Karyawan') }}</option>
                            <option value="owner">{{ __('Owner') }}</option>
                        </select>
                    </div>

                    @if ($inviteRole === 'kasir')
                        <div>
                            <x-input-label value="Izin Akses Tambahan" />
                            <p class="text-xs text-slate-400 dark:text-slate-500 mb-2">{{ __('Kasir selalu bisa akses halaman Kasir. Centang halaman lain yang boleh diakses.') }}</p>
                            <div class="space-y-2">
                                @foreach ($permissions as $permission)
                                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                        <input type="checkbox" wire:model="invitePermissions" value="{{ $permission->value }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-600 dark:bg-slate-800">
                                        {{ $permission->label() }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="$set('showInviteModal', false)" class="px-4 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">{{ __('Batal') }}</button>
                        <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="sendInvite">
                            <span wire:loading.remove wire:target="sendInvite">{{ __('Kirim Undangan') }}</span>
                            <span wire:loading wire:target="sendInvite">{{ __('Mengirim...') }}</span>
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Edit Employee Modal -->
    @if ($showEditModal)
    <div class="fixed inset-0 z-50 overflow-y-auto px-4 py-6">
        <div class="fixed inset-0 bg-slate-900/60" wire:click="$set('showEditModal', false)"></div>
        <div class="relative mb-6 bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-soft sm:max-w-md sm:mx-auto">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Detail Karyawan') }}</h3>

                <form wire:submit="saveEdit" class="mt-4 space-y-4">
                    <div>
                        <x-input-label for="editName" value="Nama" />
                        <x-text-input wire:model="editName" id="editName" type="text" class="block w-full" />
                        <x-input-error :messages="$errors->get('editName')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="editRole" value="Role" />
                        <select wire:model.live="editRole" id="editRole" class="block w-full mt-1 border-slate-200 bg-slate-50/60 focus:bg-white focus:border-brand-500 focus:ring-brand-500 rounded-lg shadow-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800/60 dark:focus:bg-slate-800 dark:text-slate-100">
                            <option value="kasir">{{ __('Karyawan') }}</option>
                            <option value="owner">{{ __('Owner') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('editRole')" class="mt-2" />
                    </div>

                    @if ($editRole === 'kasir')
                        <div>
                            <x-input-label value="Izin Akses Tambahan" />
                            <p class="text-xs text-slate-400 dark:text-slate-500 mb-2">{{ __('Kasir selalu bisa akses halaman Kasir. Centang halaman lain yang boleh diakses.') }}</p>
                            <div class="space-y-2">
                                @foreach ($permissions as $permission)
                                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                        <input type="checkbox" wire:model="editPermissions" value="{{ $permission->value }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-600 dark:bg-slate-800">
                                        {{ $permission->label() }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center justify-between gap-3 pt-2">
                        <button type="button" wire:click="toggleActive({{ $editingUserId }})" wire:confirm="Yakin ingin mengubah status akun ini?" class="text-sm font-medium text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300">
                            {{ __('Nonaktifkan / Aktifkan Akun') }}
                        </button>

                        <div class="flex gap-3">
                            <button type="button" wire:click="$set('showEditModal', false)" class="px-4 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100">{{ __('Tutup') }}</button>
                            <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="saveEdit">
                                <span wire:loading.remove wire:target="saveEdit">{{ __('Simpan') }}</span>
                                <span wire:loading wire:target="saveEdit">{{ __('Menyimpan...') }}</span>
                            </x-primary-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
