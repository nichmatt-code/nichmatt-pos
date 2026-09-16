<?php

namespace Tests\Feature;

use App\Livewire\Tags\Index;
use App\Models\Store;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TagManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_a_tag(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->set('name', 'Pedas')
            ->call('save')
            ->assertHasNoErrors();

        $tag = Tag::where('name', 'Pedas')->firstOrFail();
        $this->assertSame($store->id, $tag->store_id);
    }

    public function test_owner_can_rename_and_delete_a_tag(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $tag = Tag::create(['store_id' => $store->id, 'name' => 'Pedas']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('edit', $tag->id)
            ->set('name', 'Sangat Pedas')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Sangat Pedas', $tag->fresh()->name);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('delete', $tag->id);

        $this->assertModelMissing($tag);
    }

    public function test_kasir_without_products_permission_cannot_reach_the_tags_page(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $kasir = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        $this->actingAs($kasir)->get('/tags')->assertForbidden();
    }

    public function test_kasir_with_products_permission_can_reach_the_tags_page(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $kasir = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir', 'permissions' => ['products']]);

        $this->actingAs($kasir)->get('/tags')->assertOk();
    }
}
