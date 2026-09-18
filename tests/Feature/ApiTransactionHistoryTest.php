<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTransactionHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedRequest(User $user): self
    {
        $token = $user->createToken('test')->plainTextToken;

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    private function makeTransaction(Store $store, User $user, array $overrides = []): Transaction
    {
        return Transaction::create(array_merge([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'transaction_no' => 'TRX-'.strtoupper(uniqid()),
            'subtotal' => 10000,
            'discount' => 0,
            'total' => 10000,
            'payment_method' => 'cash',
            'paid_amount' => 10000,
            'change_amount' => 0,
            'status' => 'completed',
        ], $overrides));
    }

    public function test_it_lists_transactions_within_the_default_date_range(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $this->makeTransaction($store, $user, ['transaction_no' => 'TRX-TODAY']);

        $this->authenticatedRequest($user)
            ->getJson('/api/v1/transactions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.transaction_no', 'TRX-TODAY');
    }

    public function test_it_searches_by_transaction_number_or_customer_name(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $this->makeTransaction($store, $user, ['transaction_no' => 'TRX-AAA111', 'customer_name' => 'Budi']);
        $this->makeTransaction($store, $user, ['transaction_no' => 'TRX-BBB222', 'customer_name' => 'Siti']);

        $this->authenticatedRequest($user)
            ->getJson('/api/v1/transactions?search=Budi')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.customer_name', 'Budi');

        $this->authenticatedRequest($user)
            ->getJson('/api/v1/transactions?search=BBB222')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.transaction_no', 'TRX-BBB222');
    }

    public function test_it_filters_by_a_date_range(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $old = $this->makeTransaction($store, $user, ['transaction_no' => 'TRX-OLD']);
        $old->forceFill(['created_at' => now()->subMonths(2)])->save();
        $this->makeTransaction($store, $user, ['transaction_no' => 'TRX-NEW']);

        $this->authenticatedRequest($user)
            ->getJson('/api/v1/transactions?from='.now()->subDays(3)->toDateString().'&to='.now()->toDateString())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.transaction_no', 'TRX-NEW');
    }

    public function test_it_excludes_void_transactions(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $this->makeTransaction($store, $user, ['transaction_no' => 'TRX-VOID', 'status' => 'void']);
        $this->makeTransaction($store, $user, ['transaction_no' => 'TRX-OK']);

        $this->authenticatedRequest($user)
            ->getJson('/api/v1/transactions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.transaction_no', 'TRX-OK');
    }

    public function test_a_cashier_only_sees_their_own_stores_transactions(): void
    {
        $storeA = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $storeB = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $userA = User::factory()->create(['store_id' => $storeA->id, 'role' => 'kasir']);
        $userB = User::factory()->create(['store_id' => $storeB->id, 'role' => 'kasir']);
        $this->makeTransaction($storeA, $userA, ['transaction_no' => 'TRX-STORE-A']);
        $this->makeTransaction($storeB, $userB, ['transaction_no' => 'TRX-STORE-B']);

        $this->authenticatedRequest($userA)
            ->getJson('/api/v1/transactions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.transaction_no', 'TRX-STORE-A');
    }
}
