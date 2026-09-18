<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleUser(string $id, string $name, string $email): SocialiteUser
    {
        return (new SocialiteUser)->map([
            'id' => $id,
            'name' => $name,
            'email' => $email,
        ]);
    }

    public function test_new_google_user_gets_a_new_store_with_trial(): void
    {
        Socialite::fake('google', $this->fakeGoogleUser('g-123', 'Budi Baru', 'budi.baru@example.com'));

        $response = $this->get('/auth/google/callback');

        $user = User::where('email', 'budi.baru@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('g-123', $user->google_id);
        $this->assertSame('owner', $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->store->onTrial());
        $this->assertFalse($user->password_set_by_user);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('pos'));
    }

    public function test_existing_user_email_is_linked_to_google_account(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $existing = User::factory()->create([
            'store_id' => $store->id,
            'role' => 'kasir',
            'email' => 'kasir@example.com',
            'google_id' => null,
        ]);

        Socialite::fake('google', $this->fakeGoogleUser('g-999', 'Kasir Toko', 'kasir@example.com'));

        $this->get('/auth/google/callback');

        $existing->refresh();

        $this->assertSame('g-999', $existing->google_id);
        $this->assertSame('kasir', $existing->role);
        $this->assertAuthenticatedAs($existing);
        $this->assertSame(1, User::where('email', 'kasir@example.com')->count());
    }

    public function test_returning_google_user_is_matched_by_google_id(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $existing = User::factory()->create([
            'store_id' => $store->id,
            'role' => 'owner',
            'email' => 'owner@example.com',
            'google_id' => 'g-555',
        ]);

        Socialite::fake('google', $this->fakeGoogleUser('g-555', 'Owner Toko', 'owner@example.com'));

        $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($existing);
        $this->assertSame(1, User::where('google_id', 'g-555')->count());
    }

    public function test_an_already_logged_in_user_can_add_a_second_account_via_google(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $first = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $this->actingAs($first);
        session(['linked_accounts' => [$first->id]]);

        Socialite::fake('google', $this->fakeGoogleUser('g-777', 'Akun Kedua', 'akun.kedua@example.com'));

        $this->get('/auth/google/callback')->assertRedirect(route('pos'));

        $second = User::where('email', 'akun.kedua@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($second);
        $this->assertEqualsCanonicalizing([$first->id, $second->id], session('linked_accounts'));
    }

    public function test_mobile_redirect_gets_a_token_instead_of_a_session(): void
    {
        Socialite::fake('google', $this->fakeGoogleUser('g-mobile-1', 'Kasir Mobile', 'kasir.mobile@example.com'));

        $this->get('/auth/google/redirect?mobile_redirect='.urlencode('exp://192.168.1.5:8081/--/'));

        $response = $this->get('/auth/google/callback');

        $user = User::where('email', 'kasir.mobile@example.com')->firstOrFail();

        $response->assertRedirect();
        $this->assertStringStartsWith('exp://192.168.1.5:8081/--/?token=', $response->headers->get('Location'));
        $this->assertGuest();

        $token = explode('token=', (string) $response->headers->get('Location'))[1];
        $accessToken = PersonalAccessToken::findToken(urldecode($token));

        $this->assertNotNull($accessToken);
        $this->assertSame($user->id, $accessToken->tokenable_id);
    }

    public function test_mobile_redirect_is_ignored_when_the_scheme_is_not_allowlisted(): void
    {
        Socialite::fake('google', $this->fakeGoogleUser('g-mobile-2', 'Percobaan Jahat', 'percobaan@example.com'));

        $this->get('/auth/google/redirect?mobile_redirect='.urlencode('https://evil.example.com/steal'));

        $response = $this->get('/auth/google/callback');

        $user = User::where('email', 'percobaan@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('pos'));
    }
}
