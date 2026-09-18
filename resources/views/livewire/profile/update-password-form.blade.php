<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Accounts created via Google sign-in are given a random, never-shown
     * password, so they have nothing to type into "current password" -
     * skip that requirement for them until they've set one of their own.
     */
    public function getNeedsCurrentPasswordProperty(): bool
    {
        return (bool) Auth::user()->password_set_by_user;
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        $rules = [
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ];

        if ($this->needsCurrentPassword) {
            $rules['current_password'] = ['required', 'string', 'current_password'];
        }

        try {
            $validated = $this->validate($rules);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
            'password_set_by_user' => true,
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
            {{ $this->needsCurrentPassword ? __('Ubah Password') : __('Buat Password') }}
        </h2>

        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            @if ($this->needsCurrentPassword)
                {{ __('Gunakan password yang panjang dan acak agar akun tetap aman.') }}
            @else
                {{ __('Akun Anda dibuat via Google dan belum punya password sendiri. Buat satu di sini agar bisa login dengan email & password (misalnya untuk akses API/Postman).') }}
            @endif
        </p>
    </header>

    <form wire:submit="updatePassword" class="mt-6 space-y-6">
        @if ($this->needsCurrentPassword)
            <div>
                <x-input-label for="update_password_current_password" :value="__('Current Password')" />
                <x-text-input wire:model="current_password" id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
                <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
            </div>
        @endif

        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" />
            <x-text-input wire:model="password" id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <x-text-input wire:model="password_confirmation" id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            <x-action-message class="me-3" on="password-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
