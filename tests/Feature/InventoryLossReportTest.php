<?php

namespace Tests\Feature;

use App\Livewire\Reports\InventoryLoss;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryLossReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_each_stock_opname_shortage_event_with_its_value(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $flour = InventoryItem::create(['store_id' => $store->id, 'name' => 'Tepung', 'unit' => 'gr', 'cost_price' => 10, 'stock_qty' => 500, 'min_stock' => 100]);

        InventoryMovement::create(['store_id' => $store->id, 'inventory_item_id' => $flour->id, 'user_id' => $owner->id, 'type' => 'adjustment', 'qty' => -20, 'note' => 'Stock opname SO-1']);
        // A surplus must not appear as a loss event.
        InventoryMovement::create(['store_id' => $store->id, 'inventory_item_id' => $flour->id, 'user_id' => $owner->id, 'type' => 'adjustment', 'qty' => 15, 'note' => 'Stock opname SO-2']);
        // A normal sale must not appear here either.
        InventoryMovement::create(['store_id' => $store->id, 'inventory_item_id' => $flour->id, 'user_id' => $owner->id, 'type' => 'out', 'qty' => -50, 'note' => 'Penjualan TRX-1']);

        Livewire::actingAs($owner)
            ->test(InventoryLoss::class)
            ->assertSee('Tepung')
            ->assertSee('SO-1')
            ->assertDontSee('SO-2');
    }

    public function test_search_filters_by_item_name(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $flour = InventoryItem::create(['store_id' => $store->id, 'name' => 'Tepung', 'unit' => 'gr', 'cost_price' => 10, 'stock_qty' => 500, 'min_stock' => 100]);
        $sugar = InventoryItem::create(['store_id' => $store->id, 'name' => 'Gula', 'unit' => 'gr', 'cost_price' => 20, 'stock_qty' => 500, 'min_stock' => 100]);

        InventoryMovement::create(['store_id' => $store->id, 'inventory_item_id' => $flour->id, 'user_id' => $owner->id, 'type' => 'adjustment', 'qty' => -20, 'note' => 'Stock opname SO-1']);
        InventoryMovement::create(['store_id' => $store->id, 'inventory_item_id' => $sugar->id, 'user_id' => $owner->id, 'type' => 'adjustment', 'qty' => -5, 'note' => 'Stock opname SO-1']);

        Livewire::actingAs($owner)
            ->test(InventoryLoss::class)
            ->set('search', 'Tepung')
            ->assertSee('Tepung')
            ->assertDontSee('Gula');
    }
}
