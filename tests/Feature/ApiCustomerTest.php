<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCustomerTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedRequest(User $user): self
    {
        $token = $user->createToken('test')->plainTextToken;

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    public function test_it_searches_customers_by_name_or_phone(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        Customer::create(['store_id' => $store->id, 'name' => 'Budi Santoso', 'phone' => '081111111111']);
        Customer::create(['store_id' => $store->id, 'name' => 'Siti Aminah', 'phone' => '082222222222']);

        $this->authenticatedRequest($user)
            ->getJson('/api/v1/customers?search=Budi')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Budi Santoso');

        $this->authenticatedRequest($user)
            ->getJson('/api/v1/customers?search=082222')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Siti Aminah');
    }

    public function test_it_only_searches_within_the_authenticated_store(): void
    {
        $storeA = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $storeB = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $userA = User::factory()->create(['store_id' => $storeA->id, 'role' => 'kasir']);
        Customer::create(['store_id' => $storeB->id, 'name' => 'Budi Toko B', 'phone' => '081111111111']);

        $this->authenticatedRequest($userA)
            ->getJson('/api/v1/customers?search=Budi')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_checkout_links_a_selected_customer(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $customer = Customer::create(['store_id' => $store->id, 'name' => 'Budi Santoso', 'phone' => '081111111111']);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Es Teh', 'price' => 5000, 'cost_price' => 2000, 'stock_qty' => 10,
        ]);

        $response = $this->authenticatedRequest($user)->postJson('/api/v1/transactions', [
            'items' => [['product_id' => $product->id, 'qty' => 1]],
            'customer_id' => $customer->id,
            'payment_method' => 'cash',
            'paid_amount' => 5000,
        ]);

        $response->assertCreated()->assertJsonPath('data.customer_name', 'Budi Santoso');
        $this->assertDatabaseHas('transactions', ['customer_id' => $customer->id]);
    }
}
