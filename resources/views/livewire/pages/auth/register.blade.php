<?php

use App\Models\Store;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $store_name = '';
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request. Registering creates a new
     * store (tenant) and its first user as the store's owner.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $store = Store::create([
                'name' => $validated['store_name'],
                'trial_ends_at' => now()->addDays(Store::TRIAL_DAYS),
                'subscription_status' => 'trial',
            ]);

            return User::create([
                'store_id' => $store->id,
                'role' => 'owner',
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
        });

        event(new Registered($user));

        Auth::login($user);

        $user->rememberAsLinkedAccount();

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100 tracking-tight">{{ __('Buat toko baru') }}</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Gratis 30 hari, tanpa kartu kredit.') }}</p>
    </div>

    <form wire:submit="register" class="space-y-4">
        <!-- Store Name -->
        <div>
            <x-input-label for="store_name" :value="__('Nama Toko')" />
            <x-text-input wire:model="store_name" id="store_name" class="block mt-1 w-full" type="text" name="store_name" required autofocus autocomplete="organization" />
            <x-input-error :messages="$errors->get('store_name')" class="mt-2" />
        </div>

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Nama Anda')" />
            <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" name="name" required autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input wire:model="password" id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Konfirmasi Password')" />

            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center">
            {{ __('Daftar') }}
        </x-primary-button>
    </form>

    <div class="mt-6 flex items-center gap-3">
        <div class="flex-grow border-t border-slate-200 dark:border-slate-700"></div>
        <span class="text-xs text-slate-400 dark:text-slate-500 uppercase tracking-wide">{{ __('atau') }}</span>
        <div class="flex-grow border-t border-slate-200 dark:border-slate-700"></div>
    </div>

    <a href="{{ route('auth.google.redirect') }}" class="mt-4 flex items-center justify-center gap-2 w-full border border-slate-200 rounded-lg px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 hover:border-slate-300 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:border-slate-600 transition">
        <svg class="w-4 h-4" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.52 12.27c0-.85-.08-1.67-.22-2.45H12v4.64h6.47a5.53 5.53 0 0 1-2.4 3.63v3h3.88c2.27-2.09 3.57-5.17 3.57-8.82Z"/><path fill="#34A853" d="M12 24c3.24 0 5.96-1.07 7.95-2.91l-3.88-3a7.4 7.4 0 0 1-11-3.9H.98v3.09A12 12 0 0 0 12 24Z"/><path fill="#FBBC05" d="M5.07 14.19a7.2 7.2 0 0 1 0-4.38V6.72H.98a12 12 0 0 0 0 10.56l4.09-3.09Z"/><path fill="#EA4335" d="M12 4.77c1.76 0 3.35.6 4.6 1.79l3.44-3.44C17.95 1.19 15.24 0 12 0A12 12 0 0 0 .98 6.72l4.09 3.09A7.16 7.16 0 0 1 12 4.77Z"/></svg>
        {{ __('Daftar dengan Google') }}
    </a>

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
        {{ __('Sudah punya akun?') }}
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">{{ __('Masuk di sini') }}</a>
    </p>
</div>
