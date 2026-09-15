<?php

use App\Models\EmployeeInvitation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public EmployeeInvitation $invitation;

    public string $name = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(EmployeeInvitation $invitation): void
    {
        $this->invitation = $invitation;
    }

    public function emailAlreadyRegistered(): bool
    {
        return User::where('email', $this->invitation->email)->exists();
    }

    public function accept(): void
    {
        if (! $this->invitation->isPending() || $this->emailAlreadyRegistered()) {
            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'store_id' => $this->invitation->store_id,
            'role' => $this->invitation->role,
            'permissions' => $this->invitation->permissions,
            'name' => $validated['name'],
            'email' => $this->invitation->email,
            'password' => Hash::make($validated['password']),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        $this->invitation->update(['accepted_at' => now()]);

        event(new Registered($user));

        Auth::login($user);

        $user->rememberAsLinkedAccount();

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    @if ($invitation->isExpired())
        <div class="text-center">
            <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100 tracking-tight">{{ __('Undangan Kedaluwarsa') }}</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ __('Minta owner toko untuk mengirim ulang undangan.') }}</p>
        </div>
    @elseif ($invitation->accepted_at)
        <div class="text-center">
            <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100 tracking-tight">{{ __('Undangan Sudah Digunakan') }}</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                {{ __('Silakan') }}
                <a href="{{ route('login') }}" wire:navigate class="text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300 font-medium">{{ __('masuk') }}</a>
                {{ __('dengan akun Anda.') }}
            </p>
        </div>
    @elseif ($this->emailAlreadyRegistered())
        <div class="text-center">
            <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100 tracking-tight">{{ __('Email Sudah Terdaftar') }}</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                {{ __('Email :email sudah punya akun sendiri, jadi tidak bisa dipakai untuk bergabung ke toko lain. Silakan', ['email' => $invitation->email]) }}
                <a href="{{ route('login') }}" wire:navigate class="text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300 font-medium">{{ __('masuk') }}</a>
                {{ __('dengan akun tersebut, atau minta owner mengundang dengan email lain.') }}
            </p>
        </div>
    @else
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100 tracking-tight">{{ __('Gabung dengan :store', ['store' => $invitation->store->name]) }}</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $invitation->email }}</p>
        </div>

        <form wire:submit="accept" class="space-y-4">
            <div>
                <x-input-label for="name" value="Nama Lengkap" />
                <x-text-input wire:model="name" id="name" type="text" class="block w-full" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" value="Password" />
                <x-text-input wire:model="password" id="password" type="password" class="block w-full" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" value="Konfirmasi Password" />
                <x-text-input wire:model="password_confirmation" id="password_confirmation" type="password" class="block w-full" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <x-primary-button class="w-full justify-center">{{ __('Buat Akun & Gabung') }}</x-primary-button>
        </form>
    @endif
</div>
