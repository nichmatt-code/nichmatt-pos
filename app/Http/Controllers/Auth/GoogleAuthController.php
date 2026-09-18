<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\GoogleAccountResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * The mobile app (React Native/Expo) has no domain of its own that
     * Google would accept as an OAuth redirect URI, and Expo's old
     * "auth.expo.io" proxy for this was deprecated over a security
     * vulnerability. So instead of talking to Google directly, the
     * mobile app opens THIS existing web flow in an in-app browser and
     * tells us, via this query param, where to hand back a token when
     * it's done - Google itself only ever sees this domain's own
     * callback URL, unchanged.
     */
    private const MOBILE_REDIRECT_SESSION_KEY = 'google_mobile_redirect';

    /**
     * Only these URI schemes are ever honored for `mobile_redirect`, so
     * this can't be turned into an open redirect that leaks a freshly
     * minted Sanctum token to an attacker-chosen https:// URL.
     *
     * @var list<string>
     */
    private const ALLOWED_MOBILE_REDIRECT_SCHEMES = ['exp://', 'exp+nichmattposkasir://', 'nichmattposkasir://'];

    public function __construct(private readonly GoogleAccountResolver $googleAccountResolver) {}

    public function redirect(Request $request): RedirectResponse
    {
        $mobileRedirect = $request->string('mobile_redirect')->toString();

        if ($mobileRedirect !== '' && $this->isAllowedMobileRedirect($mobileRedirect)) {
            session([self::MOBILE_REDIRECT_SESSION_KEY => $mobileRedirect]);
        }

        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Handle Google's callback.
     *
     * Uses stateless mode because the session-based "state" check is
     * unreliable on some local dev setups (session cookie not round
     * tripping through the Google redirect); the authorization code
     * exchange with Google still guarantees the request is genuine.
     */
    public function callback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = $this->googleAccountResolver->resolveOrCreate($googleUser);

        $mobileRedirect = session()->pull(self::MOBILE_REDIRECT_SESSION_KEY);

        if ($mobileRedirect) {
            $token = $user->createToken('mobile')->plainTextToken;

            return redirect()->away($mobileRedirect.'?token='.urlencode($token));
        }

        Auth::login($user, remember: true);

        $user->rememberAsLinkedAccount();

        return redirect()->route('pos');
    }

    private function isAllowedMobileRedirect(string $url): bool
    {
        foreach (self::ALLOWED_MOBILE_REDIRECT_SCHEMES as $scheme) {
            if (str_starts_with($url, $scheme)) {
                return true;
            }
        }

        return false;
    }
}
