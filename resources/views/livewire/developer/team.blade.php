<div class="space-y-6">
    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6">
        <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Beri Akses Developer') }}</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Email harus sudah punya akun terdaftar (di toko mana pun) untuk bisa diberi akses.') }}</p>

        <form wire:submit="grant" class="mt-4 flex flex-wrap items-start gap-3">
            <div class="flex-1 min-w-[240px]">
                <x-text-input wire:model="email" type="email" class="block w-full" placeholder="email@contoh.com" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <x-primary-button type="submit">{{ __('Beri Akses') }}</x-primary-button>
        </form>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Punya Akses Developer') }}</h3>
        </div>
        <table class="min-w-full text-sm">
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($developers as $developer)
                    <tr wire:key="developer-{{ $developer->id }}">
                        <td class="px-6 py-3">
                            <div class="text-slate-900 dark:text-slate-100 font-medium">{{ $developer->name }}</div>
                            <div class="text-xs text-slate-400 dark:text-slate-500">{{ $developer->email }} &middot; {{ $developer->store?->name ?? __('Tanpa toko') }}</div>
                        </td>
                        <td class="px-6 py-3 text-right">
                            @if ($developer->email === \App\Models\User::BOOTSTRAP_DEVELOPER_EMAIL)
                                <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('Akun utama') }}</span>
                            @else
                                <button wire:click="revoke({{ $developer->id }})" wire:confirm="Cabut akses developer untuk {{ $developer->name }}?" class="text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300 font-medium">{{ __('Cabut Akses') }}</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
