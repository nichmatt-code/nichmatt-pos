<?php

namespace App\Services;

use App\Models\Store;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class GoogleAccountResolver
{
    /**
     * Link the Google account to an existing user by email, or create a
     * brand new store + owner if the email isn't registered yet.
     *
     * Shared by the web login (GoogleAuthController, browser session) and
     * the mobile API login (Api\GoogleAuthController, Sanctum token) so
     * both paths behave identically instead of drifting apart.
     *
     * @throws ValidationException
     */
    public function resolveOrCreate(SocialiteUser $googleUser): User
    {
        $user = User::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            if (! $user->is_active) {
                throw ValidationException::withMessages([
                    'email' => 'Akun ini sudah dinonaktifkan.',
                ]);
            }

            $user->fill(['google_id' => $googleUser->getId()]);

            if (! $user->email_verified_at) {
                $user->email_verified_at = now();
            }

            $user->save();

            return $user;
        }

        $user = DB::transaction(function () use ($googleUser) {
            $store = Store::create([
                'name' => 'Toko '.$googleUser->getName(),
                'trial_ends_at' => now()->addDays(Store::TRIAL_DAYS),
                'subscription_status' => 'trial',
            ]);

            $newUser = User::create([
                'store_id' => $store->id,
                'role' => 'owner',
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'password' => Hash::make(Str::random(32)),
                'password_set_by_user' => false,
            ]);

            $newUser->forceFill(['email_verified_at' => now()])->save();

            return $newUser;
        });

        event(new Registered($user));

        return $user;
    }
}
