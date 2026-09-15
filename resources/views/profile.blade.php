<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-slate-900 dark:text-slate-100 tracking-tight">{{ __('Profil') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Kelola informasi akun dan keamanan Anda.') }}</p>
    </x-slot>

    <div class="max-w-3xl space-y-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6 sm:p-8">
            <livewire:profile.update-profile-information-form />
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6 sm:p-8">
            <livewire:profile.update-password-form />
        </div>

        <div class="bg-white dark:bg-slate-900 border border-rose-100 dark:border-rose-900/50 shadow-card rounded-2xl p-6 sm:p-8">
            <livewire:profile.delete-user-form />
        </div>
    </div>
</x-app-layout>
