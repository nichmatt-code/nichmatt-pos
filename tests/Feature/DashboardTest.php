<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\LossRecord;
use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_without_dashboard_permission_is_redirected_to_pos(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $kasir = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        Livewire::actingAs($kasir)
            ->test(Dashboard::class)
            ->assertRedirect(route('pos'));
    }

    public function test_sales_trend_covers_the_last_14_days_and_reflects_completed_transactions(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $this->createCompletedTransaction($store->id, $owner->id, 50000, now());
        $this->createCompletedTransaction($store->id, $owner->id, 30000, now()->subDays(3));
        // Outside the 14-day window - must not be counted.
        $this->createCompletedTransaction($store->id, $owner->id, 99999, now()->subDays(20));

        $component = Livewire::actingAs($owner)->test(Dashboard::class);
        $trend = $component->viewData('salesTrend');

        $this->assertCount(14, $trend);
        $this->assertSame(80000, collect($trend)->sum('total'));
        $this->assertTrue(collect($trend)->last()['date']->isToday());
    }

    public function test_dashboard_shows_todays_inventory_usage_and_low_stock_inventory(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $flour = InventoryItem::create(['store_id' => $store->id, 'name' => 'Tepung', 'unit' => 'gr', 'stock_qty' => 500, 'min_stock' => 1000]);

        InventoryMovement::create(['store_id' => $store->id, 'inventory_item_id' => $flour->id, 'user_id' => $owner->id, 'type' => 'out', 'qty' => -200]);
        InventoryMovement::create(['store_id' => $store->id, 'inventory_item_id' => $flour->id, 'user_id' => $owner->id, 'type' => 'out', 'qty' => -50]);
        // A stock-in movement must not count as "usage".
        InventoryMovement::create(['store_id' => $store->id, 'inventory_item_id' => $flour->id, 'user_id' => $owner->id, 'type' => 'in', 'qty' => 1000]);

        Livewire::actingAs($owner)
            ->test(Dashboard::class)
            ->assertSee('Tepung')
            ->assertSee('250')
            ->assertSee('Inventory Menipis');
    }

    public function test_summary_breaks_omzet_transactions_and_losses_by_today_week_and_month(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $this->createCompletedTransaction($store->id, $owner->id, 50000, now());
        $this->createCompletedTransaction($store->id, $owner->id, 30000, now()->startOfMonth());
        // Before this month - must not be counted anywhere.
        $this->createCompletedTransaction($store->id, $owner->id, 99999, now()->subMonths(2));

        $lossToday = LossRecord::create(['store_id' => $store->id, 'user_id' => $owner->id, 'loss_no' => 'LOSS-1', 'reason' => 'Jatuh', 'total_cost_value' => 5000]);
        $lossOldThisMonth = LossRecord::create(['store_id' => $store->id, 'user_id' => $owner->id, 'loss_no' => 'LOSS-2', 'reason' => 'Rusak', 'total_cost_value' => 7000]);
        $lossOldThisMonth->forceFill(['created_at' => now()->startOfMonth()])->save();

        $component = Livewire::actingAs($owner)->test(Dashboard::class);
        $summary = $component->viewData('summary');

        $this->assertSame(50000, $summary['today']['omzet']);
        $this->assertSame(1, $summary['today']['count']);
        $this->assertSame(5000, $summary['today']['loss']);

        $this->assertSame(80000, $summary['month']['omzet']);
        $this->assertSame(2, $summary['month']['count']);
        $this->assertSame(12000, $summary['month']['loss']);
    }

    public function test_summary_values_the_inventory_shrinkage_found_by_stock_opname(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $flour = InventoryItem::create(['store_id' => $store->id, 'name' => 'Tepung', 'unit' => 'gr', 'cost_price' => 10, 'stock_qty' => 500, 'min_stock' => 100]);

        InventoryMovement::create(['store_id' => $store->id, 'inventory_item_id' => $flour->id, 'user_id' => $owner->id, 'type' => 'adjustment', 'qty' => -20, 'note' => 'Stock opname SO-1']);
        // A surplus must not count as a loss.
        InventoryMovement::create(['store_id' => $store->id, 'inventory_item_id' => $flour->id, 'user_id' => $owner->id, 'type' => 'adjustment', 'qty' => 15, 'note' => 'Stock opname SO-2']);

        $summary = Livewire::actingAs($owner)->test(Dashboard::class)->viewData('summary');

        // 20gr short x Rp10 cost price = Rp200.
        $this->assertSame(200, $summary['today']['inventory_lost']);
    }

    public function test_best_sellers_this_month_are_shown(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        Product::create(['store_id' => $store->id, 'name' => 'Nasi Goreng', 'price' => 15000, 'cost_price' => 7000, 'stock_qty' => 10]);

        $transaction = $this->createCompletedTransaction($store->id, $owner->id, 30000, now());
        TransactionItem::create(['transaction_id' => $transaction->id, 'product_name' => 'Nasi Goreng', 'price' => 15000, 'cost_price' => 7000, 'qty' => 2, 'subtotal' => 30000]);

        Livewire::actingAs($owner)
            ->test(Dashboard::class)
            ->assertSee('Produk Terlaris Bulan Ini')
            ->assertSee('Nasi Goreng');
    }

    private function createCompletedTransaction(int $storeId, int $userId, int $total, Carbon $createdAt): Transaction
    {
        $transaction = Transaction::create([
            'store_id' => $storeId,
            'user_id' => $userId,
            'transaction_no' => 'TRX-'.uniqid(),
            'subtotal' => $total,
            'discount' => 0,
            'total' => $total,
            'payment_method' => 'cash',
            'paid_amount' => $total,
            'change_amount' => 0,
            'status' => 'completed',
        ]);

        // created_at isn't mass-assignable, so backdate it separately.
        $transaction->forceFill(['created_at' => $createdAt])->save();

        return $transaction;
    }
}
