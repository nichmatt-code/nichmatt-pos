<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Store;
use App\Models\Transaction;
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
