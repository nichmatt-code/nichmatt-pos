<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_kasir_without_permissions_cannot_access_products(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $kasir = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        $this->actingAs($kasir)->get('/products')->assertForbidden();
    }

    public function test_kasir_with_products_permission_can_access_products(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $kasir = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir', 'permissions' => ['products']]);

        $this->actingAs($kasir)->get('/products')->assertOk();
        $this->actingAs($kasir)->get('/categories')->assertForbidden();
    }

    public function test_kasir_always_has_pos_access_regardless_of_permissions(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $kasir = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir', 'permissions' => []]);

        $this->actingAs($kasir)->get('/pos')->assertOk();
    }

    public function test_owner_bypasses_every_permission_check(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        foreach (['/products', '/categories', '/reports/sales', '/team', '/branch'] as $uri) {
            $this->actingAs($owner)->get($uri)->assertOk();
        }
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        User::factory()->create([
            'store_id' => $store->id,
            'role' => 'kasir',
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);

        Volt::test('pages.auth.login')
            ->set('form.email', 'inactive@example.com')
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasErrors();

        $this->assertGuest();
    }
}
