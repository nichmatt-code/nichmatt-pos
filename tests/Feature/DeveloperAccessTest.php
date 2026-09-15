<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeveloperAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_developer_cannot_access_developer_routes(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $this->actingAs($owner)->get('/developer/stores')->assertForbidden();
        $this->actingAs($owner)->get('/developer/team')->assertForbidden();
    }

    public function test_developer_can_access_developer_routes(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $developer = User::factory()->create([
            'store_id' => $store->id,
            'role' => 'owner',
            'is_developer' => true,
        ]);

        $this->actingAs($developer)->get('/developer/stores')->assertOk();
        $this->actingAs($developer)->get('/developer/team')->assertOk();
        $this->actingAs($developer)->get("/developer/stores/{$store->id}")->assertOk();
    }

    public function test_developer_route_is_reachable_even_when_own_stores_access_has_lapsed(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->subDays(5)]);
        $developer = User::factory()->create([
            'store_id' => $store->id,
            'role' => 'owner',
            'is_developer' => true,
        ]);

        $this->actingAs($developer)->get('/dashboard')->assertRedirect(route('billing.subscribe'));
        $this->actingAs($developer)->get('/developer/stores')->assertOk();
    }

    public function test_developer_stores_list_sees_data_across_every_tenant(): void
    {
        $ownStore = Store::factory()->create(['name' => 'Toko Saya', 'trial_ends_at' => now()->addDays(10)]);
        $developer = User::factory()->create([
            'store_id' => $ownStore->id,
            'role' => 'owner',
            'is_developer' => true,
        ]);

        $otherStore = Store::factory()->create(['name' => 'Toko Orang Lain', 'trial_ends_at' => now()->addDays(3)]);
        Product::factory()->create(['store_id' => $otherStore->id, 'name' => 'Produk Tenant Lain', 'price' => 10000]);

        Livewire::actingAs($developer)
            ->test('developer.stores')
            ->assertSee('Toko Saya')
            ->assertSee('Toko Orang Lain');
    }

    public function test_developer_store_detail_shows_cross_tenant_product_counts(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $developer = User::factory()->create([
            'store_id' => $store->id,
            'role' => 'owner',
            'is_developer' => true,
        ]);

        $otherStore = Store::factory()->create(['trial_ends_at' => now()->addDays(3)]);
        Product::factory()->count(3)->sequence(
            ['name' => 'Produk A'],
            ['name' => 'Produk B'],
            ['name' => 'Produk C'],
        )->create(['store_id' => $otherStore->id, 'price' => 10000]);

        Livewire::actingAs($developer)
            ->test('developer.store-show', ['store' => $otherStore])
            ->assertSee('3');
    }

    public function test_granting_developer_access_by_email(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $developer = User::factory()->create([
            'store_id' => $store->id,
            'role' => 'owner',
            'is_developer' => true,
        ]);

        $target = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        Livewire::actingAs($developer)
            ->test('developer.team')
            ->set('email', $target->email)
            ->call('grant');

        $this->assertTrue($target->fresh()->isDeveloper());
    }

    public function test_granting_developer_access_to_unknown_email_fails_gracefully(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $developer = User::factory()->create([
            'store_id' => $store->id,
            'role' => 'owner',
            'is_developer' => true,
        ]);

        Livewire::actingAs($developer)
            ->test('developer.team')
            ->set('email', 'tidak-ada@example.com')
            ->call('grant')
            ->assertHasErrors('email');
    }

    public function test_revoking_developer_access(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $developer = User::factory()->create([
            'store_id' => $store->id,
            'role' => 'owner',
            'is_developer' => true,
        ]);

        $other = User::factory()->create([
            'store_id' => $store->id,
            'role' => 'kasir',
            'is_developer' => true,
        ]);

        Livewire::actingAs($developer)
            ->test('developer.team')
            ->call('revoke', $other->id);

        $this->assertFalse($other->fresh()->isDeveloper());
    }

    public function test_bootstrap_developer_email_cannot_be_revoked(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $developer = User::factory()->create([
            'store_id' => $store->id,
            'role' => 'owner',
            'is_developer' => true,
        ]);

        $bootstrap = User::factory()->create([
            'store_id' => $store->id,
            'role' => 'owner',
            'email' => User::BOOTSTRAP_DEVELOPER_EMAIL,
        ]);

        Livewire::actingAs($developer)
            ->test('developer.team')
            ->call('revoke', $bootstrap->id)
            ->assertHasErrors('email');

        $this->assertTrue($bootstrap->fresh()->isDeveloper());
    }

    public function test_new_account_with_bootstrap_email_automatically_becomes_developer(): void
    {
        $store = Store::factory()->create();

        $user = User::factory()->create([
            'store_id' => $store->id,
            'role' => 'owner',
            'email' => User::BOOTSTRAP_DEVELOPER_EMAIL,
        ]);

        $this->assertTrue($user->isDeveloper());
    }
}
