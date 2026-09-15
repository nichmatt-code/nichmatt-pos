<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_see_the_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Daftar Gratis');
    }

    public function test_authenticated_users_also_see_the_landing_page(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('Buka Dashboard');
    }
}
