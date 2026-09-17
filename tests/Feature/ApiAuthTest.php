<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_log_in_and_receive_an_api_token(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create([
            'store_id' => $store->id,
            'role' => 'kasir',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password123',
            'device_name' => 'Pixel 7',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role', 'store']])
            ->assertJsonPath('user.email', $user->email);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Pixel 7',
        ]);
    }

    public function test_login_fails_with_the_wrong_password(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'password' => bcrypt('password123')]);

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'device_name' => 'Pixel 7',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_an_inactive_user_cannot_log_in(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'password' => bcrypt('password123'), 'is_active' => false]);

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password123',
            'device_name' => 'Pixel 7',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_an_authenticated_user_can_fetch_their_profile(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.store.id', $store->id);
    }

    public function test_a_guest_cannot_fetch_the_profile_endpoint(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_logging_out_revokes_only_the_current_token(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id]);
        $currentToken = $user->createToken('current-device')->plainTextToken;
        $user->createToken('other-device');

        $this->withHeader('Authorization', 'Bearer '.$currentToken)
            ->postJson('/api/v1/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'other-device']);
    }
}
