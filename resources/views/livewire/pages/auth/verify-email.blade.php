<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('pos', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100 tracking-tight">{{ __('Verifikasi email Anda') }}</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            {{ __('Terima kasih sudah mendaftar! Sebelum memulai, mohon verifikasi email Anda dengan mengklik link yang baru saja kami kirimkan. Belum menerima email? Kami akan kirimkan lagi.') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/60 px-4 py-3 font-medium text-sm text-emerald-700 dark:text-emerald-300">
            {{ __('Link verifikasi baru telah dikirim ke email yang Anda daftarkan.') }}
        </div>
    @endif

    <div class="flex items-center justify-between">
        <x-primary-button wire:click="sendVerification">
            {{ __('Kirim Ulang Email') }}
        </x-primary-button>

        <button wire:click="logout" type="submit" class="text-sm text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
            {{ __('Keluar') }}
        </button>
    </div>
</div>
