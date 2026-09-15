<?php

namespace App\Livewire\Developer;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Team extends Component
{
    public string $email = '';

    /**
     * Grant developer (cross-tenant) access to an existing account.
     */
    public function grant(): void
    {
        $validated = $this->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            $this->addError('email', 'Tidak ada akun terdaftar dengan email ini.');

            return;
        }

        if ($user->isDeveloper()) {
            $this->addError('email', 'Akun ini sudah punya akses developer.');

            return;
        }

        $user->update(['is_developer' => true]);

        $this->reset('email');
    }

    /**
     * Revoke developer access. The bootstrap account can never be revoked
     * here, so there's always at least one way back in.
     */
    public function revoke(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->email === User::BOOTSTRAP_DEVELOPER_EMAIL) {
            $this->addError('email', 'Akses developer akun ini tidak bisa dicabut.');

            return;
        }

        $user->update(['is_developer' => false]);
    }

    public function render(): View
    {
        return view('livewire.developer.team', [
            'developers' => User::where('is_developer', true)->with('store')->orderBy('name')->get(),
        ]);
    }
}
