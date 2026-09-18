<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiLossRecordTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedRequest(User $user): self
    {
        $token = $user->createToken('test')->plainTextToken;

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    public function test_it_records_a_loss_and_deducts_stock_without_creating_a_sale(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Kopi Susu', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 12,
        ]);

        $response = $this->authenticatedRequest($user)->postJson('/api/v1/loss-records', [
            'items' => [['product_id' => $product->id, 'qty' => 2]],
            'reason' => 'Gelas pecah',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.reason', 'Gelas pecah')
            ->assertJsonPath('data.total_cost_value', 20000)
            ->assertJsonPath('data.items.0.product_id', $product->id)
            ->assertJsonPath('data.items.0.subtotal_cost', 20000);

        $this->assertSame(10, $product->fresh()->stock_qty);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'out',
            'qty' => -2,
        ]);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_unlimited_stock_products_are_not_decremented(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Air Mineral', 'price' => 3000, 'cost_price' => 1500,
            'stock_qty' => 0, 'is_unlimited_stock' => true,
        ]);

        $this->authenticatedRequest($user)->postJson('/api/v1/loss-records', [
            'items' => [['product_id' => $product->id, 'qty' => 3]],
            'reason' => 'Kadaluarsa',
        ])->assertCreated();

        $this->assertSame(0, $product->fresh()->stock_qty);
    }

    public function test_a_reason_is_required(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Kopi Susu', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 12,
        ]);

        $this->authenticatedRequest($user)->postJson('/api/v1/loss-records', [
            'items' => [['product_id' => $product->id, 'qty' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors('reason');
    }
}
