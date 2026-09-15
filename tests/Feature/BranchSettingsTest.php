<?php

namespace Tests\Feature;

use App\Livewire\Branch\Settings;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BranchSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_branch_information(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10), 'name' => 'Toko Lama']);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Settings::class)
            ->set('name', 'Toko Baru')
            ->set('address', 'Jl. Baru No. 1')
            ->set('phone', '08123456789')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Toko Baru', $store->fresh()->name);
        $this->assertSame('Jl. Baru No. 1', $store->fresh()->address);
    }

    public function test_kasir_without_permission_cannot_reach_branch_settings_page(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $kasir = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        $this->actingAs($kasir)->get('/branch')->assertForbidden();
    }

    public function test_kasir_with_store_settings_permission_can_reach_branch_settings_page(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $kasir = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir', 'permissions' => ['store-settings']]);

        $this->actingAs($kasir)->get('/branch')->assertOk();
    }
}
