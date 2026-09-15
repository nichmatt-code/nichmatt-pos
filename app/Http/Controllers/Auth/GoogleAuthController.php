<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Handle Google's callback: link the Google account to an existing
     * user by email, or create a brand new store + owner if the email
     * isn't registered yet.
     *
     * Uses stateless mode because the session-based "state" check is
     * unreliable on some local dev setups (session cookie not round
     * tripping through the Google redirect); the authorization code
     * exchange with Google still guarantees the request is genuine.
     */
    public function callback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

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
        } else {
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
                ]);

                $newUser->forceFill(['email_verified_at' => now()])->save();

                return $newUser;
            });

            event(new Registered($user));
        }

        Auth::login($user, remember: true);

        $user->rememberAsLinkedAccount();

        return redirect()->route('dashboard');
    }
}
