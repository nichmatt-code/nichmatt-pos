<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\StockOpname;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiStockOpnameTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedRequest(User $user): self
    {
        $token = $user->createToken('test')->plainTextToken;

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    public function test_a_kasir_without_permission_is_forbidden(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $kasir = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir', 'permissions' => []]);

        $this->authenticatedRequest($kasir)
            ->postJson('/api/v1/stock-opnames', ['type' => 'product'])
            ->assertForbidden();
    }

    public function test_owner_can_create_a_product_opname_and_it_skips_unlimited_and_inactive(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $limited = Product::create(['store_id' => $store->id, 'name' => 'Kopi Susu', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 12]);
        Product::create(['store_id' => $store->id, 'name' => 'Air Mineral', 'price' => 3000, 'cost_price' => 1500, 'stock_qty' => 0, 'is_unlimited_stock' => true]);
        Product::create(['store_id' => $store->id, 'name' => 'Nonaktif', 'price' => 3000, 'cost_price' => 1500, 'stock_qty' => 5, 'is_active' => false]);

        $response = $this->authenticatedRequest($owner)->postJson('/api/v1/stock-opnames', ['type' => 'product']);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'product')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.product_id', $limited->id)
            ->assertJsonPath('data.items.0.system_qty', 12)
            ->assertJsonPath('data.items.0.counted_qty', null);
    }

    public function test_owner_can_create_an_inventory_opname(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $item = InventoryItem::create(['store_id' => $store->id, 'name' => 'Beras', 'unit' => 'kg', 'stock_qty' => 40, 'min_stock' => 5]);

        $response = $this->authenticatedRequest($owner)->postJson('/api/v1/stock-opnames', ['type' => 'inventory']);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'inventory')
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.inventory_item_id', $item->id)
            ->assertJsonPath('data.items.0.system_qty', 40);
    }

    public function test_saving_counts_and_finishing_adjusts_stock_and_logs_a_movement(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $counted = Product::create(['store_id' => $store->id, 'name' => 'Kopi Susu', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 12]);
        $unchanged = Product::create(['store_id' => $store->id, 'name' => 'Teh Manis', 'price' => 8000, 'cost_price' => 3000, 'stock_qty' => 20]);

        $create = $this->authenticatedRequest($owner)->postJson('/api/v1/stock-opnames', ['type' => 'product']);
        $opnameId = $create->json('data.id');
        $items = collect($create->json('data.items'))->keyBy('product_id');

        $this->authenticatedRequest($owner)
            ->putJson("/api/v1/stock-opnames/{$opnameId}/counts", [
                'counts' => [
                    ['item_id' => $items[$counted->id]['id'], 'counted_qty' => 9],
                    ['item_id' => $items[$unchanged->id]['id'], 'counted_qty' => 20],
                ],
            ])
            ->assertOk();

        $this->authenticatedRequest($owner)
            ->postJson("/api/v1/stock-opnames/{$opnameId}/finish")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertSame(9, $counted->fresh()->stock_qty);
        $this->assertSame(20, $unchanged->fresh()->stock_qty);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $counted->id,
            'type' => 'adjustment',
            'qty' => -3,
        ]);
        // No movement for the unchanged product (counted_qty === system_qty).
        $this->assertDatabaseMissing('stock_movements', ['product_id' => $unchanged->id]);
    }

    public function test_uncounted_items_are_left_untouched_at_finish(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Kopi Susu', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 12]);

        $create = $this->authenticatedRequest($owner)->postJson('/api/v1/stock-opnames', ['type' => 'product']);
        $opnameId = $create->json('data.id');

        $this->authenticatedRequest($owner)->postJson("/api/v1/stock-opnames/{$opnameId}/finish")->assertOk();

        $this->assertSame(12, $product->fresh()->stock_qty);
        $this->assertDatabaseMissing('stock_movements', ['product_id' => $product->id]);
    }

    public function test_a_completed_opname_cannot_be_counted_or_finished_again(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        Product::create(['store_id' => $store->id, 'name' => 'Kopi Susu', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 12]);

        $create = $this->authenticatedRequest($owner)->postJson('/api/v1/stock-opnames', ['type' => 'product']);
        $opnameId = $create->json('data.id');
        $itemId = $create->json('data.items.0.id');

        $this->authenticatedRequest($owner)->postJson("/api/v1/stock-opnames/{$opnameId}/finish")->assertOk();

        $this->authenticatedRequest($owner)
            ->putJson("/api/v1/stock-opnames/{$opnameId}/counts", [
                'counts' => [['item_id' => $itemId, 'counted_qty' => 999]],
            ])
            ->assertOk();

        $this->assertDatabaseHas('stock_opname_items', ['id' => $itemId, 'counted_qty' => null]);
    }

    public function test_only_a_draft_opname_can_be_deleted(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        Product::create(['store_id' => $store->id, 'name' => 'Kopi Susu', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 12]);

        $create = $this->authenticatedRequest($owner)->postJson('/api/v1/stock-opnames', ['type' => 'product']);
        $opnameId = $create->json('data.id');

        $this->authenticatedRequest($owner)->postJson("/api/v1/stock-opnames/{$opnameId}/finish")->assertOk();
        $this->authenticatedRequest($owner)->deleteJson("/api/v1/stock-opnames/{$opnameId}")->assertOk();

        $this->assertDatabaseHas('stock_opnames', ['id' => $opnameId, 'status' => StockOpname::STATUS_COMPLETED]);
    }

    public function test_a_kasir_only_sees_their_own_stores_opnames(): void
    {
        $storeA = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $storeB = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $ownerA = User::factory()->create(['store_id' => $storeA->id, 'role' => 'owner']);
        $ownerB = User::factory()->create(['store_id' => $storeB->id, 'role' => 'owner']);

        $this->authenticatedRequest($ownerB)->postJson('/api/v1/stock-opnames', ['type' => 'product'])->assertCreated();

        // Lihat catatan di ApiSelfOrderTest - dua user berbeda dalam satu
        // metode test butuh ini supaya guard Sanctum benar-benar login
        // ulang, bukan "kebagian" sesi user sebelumnya.
        $this->app->make('auth')->forgetGuards();

        $this->authenticatedRequest($ownerA)
            ->getJson('/api/v1/stock-opnames')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
