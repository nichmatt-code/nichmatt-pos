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
            ->getJson('/api/v1/customers/search?search=Budi')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Budi Santoso');

        $this->authenticatedRequest($user)
            ->getJson('/api/v1/customers/search?search=082222')
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
            ->getJson('/api/v1/customers/search?search=Budi')
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

    public function test_a_kasir_without_customers_permission_is_forbidden(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $kasir = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir', 'permissions' => []]);

        $this->authenticatedRequest($kasir)
            ->getJson('/api/v1/customers')
            ->assertForbidden();
    }

    public function test_owner_can_list_customers_with_transaction_counts(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $customer = Customer::create(['store_id' => $store->id, 'name' => 'Budi', 'phone' => '081234567890']);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Kopi Susu', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 20,
        ]);
        $this->authenticatedRequest($owner)->postJson('/api/v1/transactions', [
            'items' => [['product_id' => $product->id, 'qty' => 1]],
            'customer_id' => $customer->id,
            'payment_method' => 'cash',
            'paid_amount' => 18000,
        ])->assertCreated();

        $this->authenticatedRequest($owner)
            ->getJson('/api/v1/customers')
            ->assertOk()
            ->assertJsonPath('data.0.id', $customer->id)
            ->assertJsonPath('data.0.transactions_count', 1);
    }

    public function test_owner_can_create_a_customer(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $response = $this->authenticatedRequest($owner)->postJson('/api/v1/customers', [
            'name' => 'Budi',
            'phone' => '081234567890',
            'address' => 'Jl. Mawar No. 1',
        ]);

        $response->assertCreated()->assertJsonPath('data.name', 'Budi');

        $customer = Customer::where('phone', '081234567890')->firstOrFail();
        $this->assertSame($store->id, $customer->store_id);
        $this->assertSame('Jl. Mawar No. 1', $customer->address);
    }

    public function test_name_and_phone_are_required(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $this->authenticatedRequest($owner)
            ->postJson('/api/v1/customers', ['name' => '', 'phone' => ''])
            ->assertJsonValidationErrors(['name', 'phone']);
    }

    public function test_birthdate_must_be_before_today(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $this->authenticatedRequest($owner)
            ->postJson('/api/v1/customers', [
                'name' => 'Budi',
                'phone' => '081234567890',
                'birthdate' => now()->addDay()->format('Y-m-d'),
            ])
            ->assertJsonValidationErrors(['birthdate']);
    }

    public function test_phone_must_be_unique_within_the_same_store(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        Customer::create(['store_id' => $store->id, 'name' => 'Budi', 'phone' => '081234567890']);

        $this->authenticatedRequest($owner)
            ->postJson('/api/v1/customers', ['name' => 'Budi Lain', 'phone' => '081234567890'])
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_two_different_stores_can_each_have_a_customer_with_the_same_phone(): void
    {
        $storeA = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $storeB = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $ownerB = User::factory()->create(['store_id' => $storeB->id, 'role' => 'owner']);
        Customer::create(['store_id' => $storeA->id, 'name' => 'Budi', 'phone' => '081234567890']);

        $this->authenticatedRequest($ownerB)
            ->postJson('/api/v1/customers', ['name' => 'Budi', 'phone' => '081234567890'])
            ->assertCreated();

        $this->assertSame(2, Customer::withoutGlobalScopes()->where('phone', '081234567890')->count());
    }

    public function test_owner_can_update_a_customer_keeping_its_own_phone(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $customer = Customer::create(['store_id' => $store->id, 'name' => 'Budi', 'phone' => '081234567890']);

        $this->authenticatedRequest($owner)
            ->putJson("/api/v1/customers/{$customer->id}", [
                'name' => 'Budi Santoso',
                'phone' => '081234567890',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Budi Santoso');

        $this->assertSame('Budi Santoso', $customer->fresh()->name);
    }

    public function test_owner_can_delete_a_customer(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $customer = Customer::create(['store_id' => $store->id, 'name' => 'Budi', 'phone' => '081234567890']);

        $this->authenticatedRequest($owner)
            ->deleteJson("/api/v1/customers/{$customer->id}")
            ->assertOk();

        $this->assertModelMissing($customer);
    }

    public function test_a_kasir_only_manages_their_own_stores_customers(): void
    {
        $storeA = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $storeB = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $ownerA = User::factory()->create(['store_id' => $storeA->id, 'role' => 'owner']);
        $ownerB = User::factory()->create(['store_id' => $storeB->id, 'role' => 'owner']);
        $customerB = Customer::create(['store_id' => $storeB->id, 'name' => 'Budi', 'phone' => '081234567890']);

        $this->authenticatedRequest($ownerB)->getJson("/api/v1/customers/{$customerB->id}")->assertOk();

        // Lihat catatan di ApiSelfOrderTest - dua user berbeda dalam satu
        // metode test butuh ini supaya guard Sanctum benar-benar login
        // ulang, bukan "kebagian" sesi user sebelumnya.
        $this->app->make('auth')->forgetGuards();

        $this->authenticatedRequest($ownerA)
            ->getJson("/api/v1/customers/{$customerB->id}")
            ->assertNotFound();
    }
}
