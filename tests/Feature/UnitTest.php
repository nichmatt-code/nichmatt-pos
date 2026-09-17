<?php

namespace Tests\Feature;

use App\Livewire\Units\Index;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_a_unit(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->set('name', 'liter')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('units', ['store_id' => $store->id, 'name' => 'liter']);
    }

    public function test_unit_names_are_unique_per_store(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        Unit::create(['store_id' => $store->id, 'name' => 'kg']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->set('name', 'kg')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_owner_can_edit_and_delete_a_unit(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $unit = Unit::create(['store_id' => $store->id, 'name' => 'kg']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('edit', $unit->id)
            ->set('name', 'kilogram')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('kilogram', $unit->fresh()->name);

        Livewire::actingAs($owner)->test(Index::class)->call('delete', $unit->id);
        $this->assertModelMissing($unit);
    }

    public function test_units_are_isolated_per_store(): void
    {
        $storeOne = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $storeTwo = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $ownerOne = User::factory()->create(['store_id' => $storeOne->id, 'role' => 'owner']);
        Unit::create(['store_id' => $storeTwo->id, 'name' => 'karung']);

        Livewire::actingAs($ownerOne)
            ->test(Index::class)
            ->assertDontSee('karung');
    }
}
