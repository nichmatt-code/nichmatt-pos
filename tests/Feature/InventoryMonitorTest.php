<?php

namespace Tests\Feature;

use App\Livewire\Reports\InventoryMonitor;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_usage_recorded_loss_opname_difference_and_loss_value(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $flour = InventoryItem::create(['store_id' => $store->id, 'name' => 'Tepung', 'unit' => 'gr', 'cost_price' => 10, 'stock_qty' => 1000, 'min_stock' => 100]);

        // 200gr used via a normal sale.
        InventoryMovement::create(['store_id' => $store->id, 'inventory_item_id' => $flour->id, 'user_id' => $owner->id, 'type' => 'out', 'qty' => -200, 'note' => 'Penjualan TRX-1']);
        // 50gr used via a package sale (note format differs but still contains "Penjualan").
        InventoryMovement::create(['store_id' => $store->id, 'inventory_item_id' => $flour->id, 'user_id' => $owner->id, 'type' => 'out', 'qty' => -50, 'note' => 'Paket Combo - Penjualan TRX-2']);
        // 30gr recorded as a loss (damaged goods).
        InventoryMovement::create(['store_id' => $store->id, 'inventory_item_id' => $flour->id, 'user_id' => $owner->id, 'type' => 'out', 'qty' => -30, 'note' => 'Kerugian LOSS-1']);
        // Stock opname found 20gr short (unexplained shrinkage).
        InventoryMovement::create(['store_id' => $store->id, 'inventory_item_id' => $flour->id, 'user_id' => $owner->id, 'type' => 'adjustment', 'qty' => -20, 'note' => 'Stock opname SO-1']);

        $component = Livewire::actingAs($owner)->test(InventoryMonitor::class);
        $row = collect($component->viewData('rows'))->first(fn ($r) => $r["item"]->id === $flour->id);

        $this->assertSame(250, $row['used']); // 200 + 50
        $this->assertSame(30, $row['recorded_loss']);
        $this->assertSame(-20, $row['opname_difference']);
        // Loss value = (recorded_loss 30 + shortage 20) * cost_price 10 = 500.
        $this->assertSame(500, $row['loss_value']);
    }

    public function test_movements_outside_the_date_range_are_excluded(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $item = InventoryItem::create(['store_id' => $store->id, 'name' => 'Gula', 'unit' => 'kg', 'cost_price' => 5000, 'stock_qty' => 100, 'min_stock' => 10]);

        $movement = InventoryMovement::create(['store_id' => $store->id, 'inventory_item_id' => $item->id, 'user_id' => $owner->id, 'type' => 'out', 'qty' => -10, 'note' => 'Penjualan TRX-OLD']);
        $movement->forceFill(['created_at' => now()->subMonths(2)])->save();

        $component = Livewire::actingAs($owner)->test(InventoryMonitor::class);
        $row = collect($component->viewData('rows'))->first(fn ($r) => $r["item"]->id === $item->id);

        $this->assertSame(0, $row['used']);
    }

    public function test_surplus_from_stock_opname_does_not_count_as_a_loss(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $item = InventoryItem::create(['store_id' => $store->id, 'name' => 'Minyak', 'unit' => 'liter', 'cost_price' => 15000, 'stock_qty' => 100, 'min_stock' => 10]);

        InventoryMovement::create(['store_id' => $store->id, 'inventory_item_id' => $item->id, 'user_id' => $owner->id, 'type' => 'adjustment', 'qty' => 15, 'note' => 'Stock opname SO-2']);

        $component = Livewire::actingAs($owner)->test(InventoryMonitor::class);
        $row = collect($component->viewData('rows'))->first(fn ($r) => $r["item"]->id === $item->id);

        $this->assertSame(15, $row['opname_difference']);
        $this->assertSame(0, $row['loss_value']);
    }
}
