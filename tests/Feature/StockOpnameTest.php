<?php

namespace Tests\Feature;

use App\Livewire\StockOpname\Index;
use App\Livewire\StockOpname\Show;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\StockOpname;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockOpnameTest extends TestCase
{
    use RefreshDatabase;

    public function test_kasir_without_permission_cannot_access_stock_opname(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $kasir = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        $this->actingAs($kasir)->get('/stock-opname')->assertForbidden();
    }

    public function test_creating_a_product_opname_snapshots_current_stock_and_skips_unlimited_products(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $limited = Product::create(['store_id' => $store->id, 'name' => 'Kopi Susu', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 12]);
        Product::create(['store_id' => $store->id, 'name' => 'Air Mineral', 'price' => 3000, 'cost_price' => 1500, 'stock_qty' => 0, 'is_unlimited_stock' => true]);
        Product::create(['store_id' => $store->id, 'name' => 'Nonaktif', 'price' => 3000, 'cost_price' => 1500, 'stock_qty' => 5, 'is_active' => false]);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->set('type', StockOpname::TYPE_PRODUCT)
            ->call('create');

        $opname = StockOpname::firstOrFail();
        $this->assertSame(StockOpname::TYPE_PRODUCT, $opname->type);
        $this->assertSame(1, $opname->items()->count());
        $this->assertSame($limited->id, $opname->items()->first()->product_id);
        $this->assertSame(12, $opname->items()->first()->system_qty);
    }

    public function test_creating_an_inventory_opname_snapshots_inventory_items(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $item = InventoryItem::create(['store_id' => $store->id, 'name' => 'Beras', 'unit' => 'kg', 'stock_qty' => 40, 'min_stock' => 5]);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->set('type', StockOpname::TYPE_INVENTORY)
            ->call('create');

        $opname = StockOpname::firstOrFail();
        $this->assertSame(StockOpname::TYPE_INVENTORY, $opname->type);
        $this->assertSame(1, $opname->items()->count());
        $this->assertSame($item->id, $opname->items()->first()->inventory_item_id);
        $this->assertSame(40, $opname->items()->first()->system_qty);
    }

    public function test_entering_a_count_persists_immediately(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Kopi Susu', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 12, 'unit' => 'pcs']);
        $opname = StockOpname::create(['store_id' => $store->id, 'code' => 'SO-TEST-1', 'type' => StockOpname::TYPE_PRODUCT, 'status' => StockOpname::STATUS_DRAFT, 'created_by' => $owner->id]);
        $opnameItem = $opname->items()->create(['product_id' => $product->id, 'item_name' => $product->name, 'unit' => $product->unit, 'system_qty' => 12]);

        Livewire::actingAs($owner)
            ->test(Show::class, ['stockOpname' => $opname])
            ->set("counts.{$opnameItem->id}", '10');

        $this->assertSame(10, $opnameItem->fresh()->counted_qty);
    }

    public function test_finishing_applies_differences_to_product_stock_and_logs_an_adjustment(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Kopi Susu', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 12, 'unit' => 'pcs']);
        $opname = StockOpname::create(['store_id' => $store->id, 'code' => 'SO-TEST-2', 'type' => StockOpname::TYPE_PRODUCT, 'status' => StockOpname::STATUS_DRAFT, 'created_by' => $owner->id]);
        $opnameItem = $opname->items()->create(['product_id' => $product->id, 'item_name' => $product->name, 'unit' => $product->unit, 'system_qty' => 12, 'counted_qty' => 9]);

        Livewire::actingAs($owner)
            ->test(Show::class, ['stockOpname' => $opname])
            ->call('finish');

        $this->assertSame(9, $product->fresh()->stock_qty);
        $this->assertSame(StockOpname::STATUS_COMPLETED, $opname->fresh()->status);
        $this->assertNotNull($opname->fresh()->completed_at);

        $movement = $product->stockMovements()->first();
        $this->assertNotNull($movement);
        $this->assertSame('adjustment', $movement->type);
        $this->assertSame(-3, $movement->qty);
    }

    public function test_finishing_applies_differences_to_inventory_stock_and_logs_an_adjustment(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $item = InventoryItem::create(['store_id' => $store->id, 'name' => 'Beras', 'unit' => 'kg', 'stock_qty' => 40, 'min_stock' => 5]);
        $opname = StockOpname::create(['store_id' => $store->id, 'code' => 'SO-TEST-3', 'type' => StockOpname::TYPE_INVENTORY, 'status' => StockOpname::STATUS_DRAFT, 'created_by' => $owner->id]);
        $opname->items()->create(['inventory_item_id' => $item->id, 'item_name' => $item->name, 'unit' => $item->unit, 'system_qty' => 40, 'counted_qty' => 45]);

        Livewire::actingAs($owner)
            ->test(Show::class, ['stockOpname' => $opname])
            ->call('finish');

        $this->assertSame(45, $item->fresh()->stock_qty);
        $movement = $item->movements()->first();
        $this->assertNotNull($movement);
        $this->assertSame('adjustment', $movement->type);
        $this->assertSame(5, $movement->qty);
    }

    public function test_finishing_skips_items_with_no_difference_and_uncounted_items(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $matching = Product::create(['store_id' => $store->id, 'name' => 'Matching', 'price' => 1000, 'cost_price' => 500, 'stock_qty' => 10, 'unit' => 'pcs']);
        $uncounted = Product::create(['store_id' => $store->id, 'name' => 'Uncounted', 'price' => 1000, 'cost_price' => 500, 'stock_qty' => 7, 'unit' => 'pcs']);

        $opname = StockOpname::create(['store_id' => $store->id, 'code' => 'SO-TEST-4', 'type' => StockOpname::TYPE_PRODUCT, 'status' => StockOpname::STATUS_DRAFT, 'created_by' => $owner->id]);
        $opname->items()->create(['product_id' => $matching->id, 'item_name' => $matching->name, 'unit' => $matching->unit, 'system_qty' => 10, 'counted_qty' => 10]);
        $opname->items()->create(['product_id' => $uncounted->id, 'item_name' => $uncounted->name, 'unit' => $uncounted->unit, 'system_qty' => 7, 'counted_qty' => null]);

        Livewire::actingAs($owner)
            ->test(Show::class, ['stockOpname' => $opname])
            ->call('finish');

        $this->assertSame(10, $matching->fresh()->stock_qty);
        $this->assertSame(7, $uncounted->fresh()->stock_qty);
        $this->assertSame(0, $matching->stockMovements()->count());
        $this->assertSame(0, $uncounted->stockMovements()->count());
    }

    public function test_a_completed_opname_cannot_be_finished_again_or_recounted(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Kopi Susu', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 12, 'unit' => 'pcs']);
        $opname = StockOpname::create([
            'store_id' => $store->id,
            'code' => 'SO-TEST-5',
            'type' => StockOpname::TYPE_PRODUCT,
            'status' => StockOpname::STATUS_COMPLETED,
            'created_by' => $owner->id,
            'completed_by' => $owner->id,
            'completed_at' => now(),
        ]);
        $opnameItem = $opname->items()->create(['product_id' => $product->id, 'item_name' => $product->name, 'unit' => $product->unit, 'system_qty' => 12, 'counted_qty' => 12]);

        Livewire::actingAs($owner)
            ->test(Show::class, ['stockOpname' => $opname])
            ->set("counts.{$opnameItem->id}", '5')
            ->call('finish');

        $this->assertSame(12, $opnameItem->fresh()->counted_qty);
        $this->assertSame(12, $product->fresh()->stock_qty);
    }

    public function test_draft_opname_can_be_deleted_but_completed_one_cannot(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $draft = StockOpname::create(['store_id' => $store->id, 'code' => 'SO-DRAFT', 'type' => StockOpname::TYPE_PRODUCT, 'status' => StockOpname::STATUS_DRAFT, 'created_by' => $owner->id]);
        $completed = StockOpname::create(['store_id' => $store->id, 'code' => 'SO-DONE', 'type' => StockOpname::TYPE_PRODUCT, 'status' => StockOpname::STATUS_COMPLETED, 'created_by' => $owner->id, 'completed_at' => now()]);

        Livewire::actingAs($owner)->test(Index::class)->call('delete', $draft->id);
        $this->assertModelMissing($draft);

        Livewire::actingAs($owner)->test(Index::class)->call('delete', $completed->id);
        $this->assertModelExists($completed);
    }

    public function test_stock_opname_sessions_are_isolated_per_store(): void
    {
        $storeOne = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $storeTwo = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $ownerOne = User::factory()->create(['store_id' => $storeOne->id, 'role' => 'owner']);
        StockOpname::create(['store_id' => $storeTwo->id, 'code' => 'SO-OTHER', 'type' => StockOpname::TYPE_PRODUCT, 'status' => StockOpname::STATUS_DRAFT]);

        Livewire::actingAs($ownerOne)
            ->test(Index::class)
            ->assertDontSee('SO-OTHER');
    }
}
