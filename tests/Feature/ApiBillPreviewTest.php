<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiBillPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prices_a_cart_without_creating_a_transaction(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Es Teh',
            'price' => 5000,
            'cost_price' => 2000,
            'stock_qty' => 30,
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/bill-preview', [
                'items' => [['product_id' => $product->id, 'qty' => 2, 'note' => 'tanpa es']],
                'customer_name' => 'Budi',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.subtotal', 10000)
            ->assertJsonPath('data.total', 10000)
            ->assertJsonStructure(['data' => ['receipt_lines']]);

        $this->assertDatabaseCount('transactions', 0);

        $lines = $response->json('data.receipt_lines');
        $this->assertIsArray($lines);
        $this->assertNotEmpty($lines);
        $this->assertStringContainsString('BELUM DIBAYAR', implode(' ', $lines));
    }

    public function test_an_unavailable_product_is_rejected(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/bill-preview', [
                'items' => [['product_id' => 999999, 'qty' => 1]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
    }
}
