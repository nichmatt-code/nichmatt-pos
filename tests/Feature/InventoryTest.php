<?php

namespace Tests\Feature;

use App\Livewire\Inventory\Index;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_kasir_without_inventory_permission_cannot_access_the_page(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $kasir = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        $this->actingAs($kasir)->get('/inventory')->assertForbidden();
    }

    public function test_owner_can_access_the_inventory_page(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $this->actingAs($owner)->get('/inventory')->assertOk();
    }

    public function test_owner_can_create_an_inventory_item(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('createItem')
            ->set('name', 'Beras')
            ->set('unit', 'kg')
            ->set('stock_qty', '50')
            ->set('min_stock', '10')
            ->call('save')
            ->assertHasNoErrors();

        $item = InventoryItem::where('name', 'Beras')->firstOrFail();
        $this->assertSame($store->id, $item->store_id);
        $this->assertSame(50, $item->stock_qty);
        $this->assertSame(10, $item->min_stock);
    }

    public function test_owner_can_set_a_cost_price_for_an_inventory_item(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('createItem')
            ->set('name', 'Tepung')
            ->set('unit', 'kg')
            ->set('cost_price', '12000')
            ->set('stock_qty', '50')
            ->set('min_stock', '10')
            ->call('save')
            ->assertHasNoErrors();

        $item = InventoryItem::where('name', 'Tepung')->firstOrFail();
        $this->assertSame(12000, $item->cost_price);
    }

    public function test_owner_can_quick_create_a_new_unit_while_editing_an_inventory_item(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('createItem')
            ->set('newUnitName', 'dus')
            ->call('addUnit')
            ->assertHasNoErrors()
            ->assertSet('unit', 'dus')
            ->assertSet('newUnitName', '');

        $this->assertDatabaseHas('units', ['store_id' => $store->id, 'name' => 'dus']);
    }

    public function test_editing_an_item_whose_unit_is_not_in_the_units_list_still_shows_it(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $item = InventoryItem::create(['store_id' => $store->id, 'name' => 'Beras', 'unit' => 'karung', 'stock_qty' => 50, 'min_stock' => 10]);
        Unit::create(['store_id' => $store->id, 'name' => 'kg']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('editItem', $item->id)
            ->assertSee('karung', false);
    }

    public function test_owner_can_edit_an_inventory_item(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $item = InventoryItem::create(['store_id' => $store->id, 'name' => 'Beras', 'unit' => 'kg', 'stock_qty' => 50, 'min_stock' => 10]);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('editItem', $item->id)
            ->set('name', 'Beras Premium')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Beras Premium', $item->fresh()->name);
    }

    public function test_owner_can_delete_an_inventory_item(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $item = InventoryItem::create(['store_id' => $store->id, 'name' => 'Beras', 'unit' => 'kg', 'stock_qty' => 50, 'min_stock' => 10]);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('delete', $item->id);

        $this->assertModelMissing($item);
    }

    public function test_adjusting_stock_updates_qty_and_logs_a_movement(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $item = InventoryItem::create(['store_id' => $store->id, 'name' => 'Beras', 'unit' => 'kg', 'stock_qty' => 50, 'min_stock' => 10]);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('openStockModal', $item->id)
            ->set('stockType', 'in')
            ->set('stockQty', '20')
            ->call('saveStock')
            ->assertHasNoErrors();

        $item->refresh();
        $this->assertSame(70, $item->stock_qty);
        $this->assertSame(1, $item->movements()->count());
        $this->assertSame(20, $item->movements()->first()->qty);
    }

    public function test_low_stock_is_flagged_when_qty_reaches_the_minimum(): void
    {
        $store = Store::factory()->create();
        $low = InventoryItem::create(['store_id' => $store->id, 'name' => 'Gula', 'unit' => 'kg', 'stock_qty' => 5, 'min_stock' => 10]);
        $ok = InventoryItem::create(['store_id' => $store->id, 'name' => 'Garam', 'unit' => 'kg', 'stock_qty' => 20, 'min_stock' => 10]);

        $this->assertTrue($low->isLowStock());
        $this->assertFalse($ok->isLowStock());
    }

    public function test_inventory_items_are_isolated_per_store(): void
    {
        $storeOne = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $storeTwo = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $ownerOne = User::factory()->create(['store_id' => $storeOne->id, 'role' => 'owner']);
        InventoryItem::create(['store_id' => $storeTwo->id, 'name' => 'Barang Toko Lain', 'unit' => 'pcs', 'stock_qty' => 10, 'min_stock' => 1]);

        Livewire::actingAs($ownerOne)
            ->test(Index::class)
            ->assertDontSee('Barang Toko Lain');
    }
}
