<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-slate-900 tracking-tight">{{ __('Profil') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Kelola informasi akun dan keamanan Anda.') }}</p>
    </x-slot>

    <div class="max-w-3xl space-y-6">
        <div class="bg-white border border-slate-200/70 shadow-card rounded-2xl p-6 sm:p-8">
            <livewire:profile.update-profile-information-form />
        </div>

        <div class="bg-white border border-slate-200/70 shadow-card rounded-2xl p-6 sm:p-8">
            <livewire:profile.update-password-form />
        </div>

        <div class="bg-white border border-rose-100 shadow-card rounded-2xl p-6 sm:p-8">
            <livewire:profile.delete-user-form />
        </div>
    </div>
</x-app-layout>
