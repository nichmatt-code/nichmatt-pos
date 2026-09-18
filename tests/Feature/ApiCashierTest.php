<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCashierTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedRequest(User $user): self
    {
        $token = $user->createToken('test')->plainTextToken;

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    public function test_it_lists_active_products_for_the_authenticated_store(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        Product::create(['store_id' => $store->id, 'name' => 'Nasi Goreng', 'price' => 20000, 'cost_price' => 12000, 'stock_qty' => 10, 'is_active' => true]);
        Product::create(['store_id' => $store->id, 'name' => 'Menu Nonaktif', 'price' => 10000, 'cost_price' => 5000, 'stock_qty' => 10, 'is_active' => false]);

        $this->authenticatedRequest($user)
            ->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Nasi Goreng');
    }

    public function test_it_lists_categories(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        Category::create(['store_id' => $store->id, 'name' => 'Makanan']);

        $this->authenticatedRequest($user)
            ->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Makanan']);
    }

    public function test_a_store_past_its_trial_is_blocked_from_the_cashier_endpoints(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->subDays(5)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        $this->authenticatedRequest($user)
            ->getJson('/api/v1/products')
            ->assertStatus(402);
    }

    public function test_it_checks_out_a_cart_into_a_transaction_and_deducts_stock(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Es Teh', 'price' => 5000, 'cost_price' => 2000, 'stock_qty' => 10, 'is_active' => true]);

        $response = $this->authenticatedRequest($user)->postJson('/api/v1/transactions', [
            'items' => [
                ['product_id' => $product->id, 'qty' => 2],
            ],
            'payment_method' => 'cash',
            'paid_amount' => 10000,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.subtotal', 10000)
            ->assertJsonPath('data.total', 10000)
            ->assertJsonPath('data.change_amount', 0)
            ->assertJsonPath('data.items.0.product_name', 'Es Teh');

        $this->assertSame(8, $product->fresh()->stock_qty);
    }

    public function test_checkout_rejects_a_price_the_client_tries_to_submit(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Es Teh', 'price' => 5000, 'cost_price' => 2000, 'stock_qty' => 10, 'is_active' => true]);

        $response = $this->authenticatedRequest($user)->postJson('/api/v1/transactions', [
            'items' => [
                ['product_id' => $product->id, 'qty' => 1, 'price' => 1],
            ],
            'payment_method' => 'cash',
            'paid_amount' => 5000,
        ]);

        $response->assertCreated()->assertJsonPath('data.subtotal', 5000);
    }

    public function test_checkout_honors_a_custom_price_when_the_store_allows_price_editing(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10), 'allow_price_edit' => true]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Es Teh', 'price' => 5000, 'cost_price' => 2000, 'stock_qty' => 10, 'is_active' => true]);

        $response = $this->authenticatedRequest($user)->postJson('/api/v1/transactions', [
            'items' => [
                ['product_id' => $product->id, 'qty' => 2, 'price' => 3000],
            ],
            'payment_method' => 'cash',
            'paid_amount' => 6000,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.subtotal', 6000)
            ->assertJsonPath('data.items.0.price', 3000);
    }

    public function test_checkout_fails_when_stock_is_insufficient(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Es Teh', 'price' => 5000, 'cost_price' => 2000, 'stock_qty' => 1, 'is_active' => true]);

        $this->authenticatedRequest($user)->postJson('/api/v1/transactions', [
            'items' => [
                ['product_id' => $product->id, 'qty' => 5],
            ],
            'payment_method' => 'cash',
            'paid_amount' => 25000,
        ])->assertUnprocessable()->assertJsonValidationErrors('items');

        $this->assertSame(1, $product->fresh()->stock_qty);
    }

    public function test_checkout_fails_when_paid_amount_is_less_than_total(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Es Teh', 'price' => 5000, 'cost_price' => 2000, 'stock_qty' => 10, 'is_active' => true]);

        $this->authenticatedRequest($user)->postJson('/api/v1/transactions', [
            'items' => [
                ['product_id' => $product->id, 'qty' => 1],
            ],
            'payment_method' => 'cash',
            'paid_amount' => 1000,
        ])->assertUnprocessable()->assertJsonValidationErrors('paid_amount');
    }

    public function test_a_cashier_can_fetch_a_transaction_from_their_own_store(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Es Teh', 'price' => 5000, 'cost_price' => 2000, 'stock_qty' => 10, 'is_active' => true]);

        $checkoutResponse = $this->authenticatedRequest($user)->postJson('/api/v1/transactions', [
            'items' => [['product_id' => $product->id, 'qty' => 1]],
            'payment_method' => 'cash',
            'paid_amount' => 5000,
        ]);

        $transactionId = $checkoutResponse->json('data.id');

        $this->authenticatedRequest($user)
            ->getJson("/api/v1/transactions/{$transactionId}")
            ->assertOk()
            ->assertJsonPath('data.id', $transactionId);
    }

    public function test_a_cashier_cannot_fetch_another_stores_transaction(): void
    {
        $storeOne = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $storeTwo = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $userOne = User::factory()->create(['store_id' => $storeOne->id, 'role' => 'kasir']);
        $userTwo = User::factory()->create(['store_id' => $storeTwo->id, 'role' => 'kasir']);

        $transaction = Transaction::create([
            'store_id' => $storeTwo->id,
            'user_id' => $userTwo->id,
            'transaction_no' => 'TRX-OTHER-STORE',
            'subtotal' => 5000,
            'discount' => 0,
            'total' => 5000,
            'payment_method' => 'cash',
            'paid_amount' => 5000,
            'change_amount' => 0,
            'status' => 'completed',
        ]);

        // A single HTTP call in this test, as the cross-store cashier - this
        // is what actually exercises the auth:sanctum + BelongsToStore
        // interaction under test; a second differently-authenticated call
        // within the same test method would reuse the framework's cached
        // Sanctum RequestGuard from the first call (a PHPUnit-only quirk,
        // never possible in a real one-request-per-process deployment).
        $this->authenticatedRequest($userOne)
            ->getJson("/api/v1/transactions/{$transaction->id}")
            ->assertNotFound();
    }
}
