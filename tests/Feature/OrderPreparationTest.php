<?php

namespace Tests\Feature;

use App\Livewire\Preparation\Index;
use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderPreparationTest extends TestCase
{
    use RefreshDatabase;

    private function makeTransaction(Store $store, User $cashier, ?\DateTimeInterface $preparedAt = null, string $status = 'completed'): Transaction
    {
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Nasi Goreng',
            'price' => 20000,
            'cost_price' => 12000,
            'stock_qty' => 10,
        ]);

        $transaction = Transaction::create([
            'store_id' => $store->id,
            'user_id' => $cashier->id,
            'transaction_no' => 'TRX-'.uniqid(),
            'subtotal' => 20000,
            'discount' => 0,
            'total' => 20000,
            'payment_method' => 'cash',
            'paid_amount' => 20000,
            'change_amount' => 0,
            'status' => $status,
            'prepared_at' => $preparedAt,
        ]);

        TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 20000,
            'cost_price' => 12000,
            'qty' => 1,
            'subtotal' => 20000,
        ]);

        return $transaction;
    }

    public function test_kasir_without_permission_cannot_access_preparation_page(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $kasir = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        $this->actingAs($kasir)->get('/preparation')->assertForbidden();
    }

    public function test_kasir_with_permission_sees_unprepared_paid_orders(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $kasir = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir', 'permissions' => ['preparation']]);
        $transaction = $this->makeTransaction($store, $kasir);

        Livewire::actingAs($kasir)
            ->test(Index::class)
            ->assertViewHas('pending', fn ($pending) => $pending->contains('id', $transaction->id))
            ->assertSee($transaction->transaction_no)
            ->call('openDetail', $transaction->id)
            ->assertSee('Nasi Goreng');
    }

    public function test_void_transactions_never_appear_in_the_queue(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $voided = $this->makeTransaction($store, $owner, status: 'void');

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->assertViewHas('pending', fn ($pending) => ! $pending->contains('id', $voided->id));
    }

    public function test_marking_an_order_prepared_moves_it_to_done(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $transaction = $this->makeTransaction($store, $owner);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('markPrepared', $transaction->id)
            ->assertViewHas('pending', fn ($pending) => $pending->isEmpty())
            ->assertViewHas('recentlyPrepared', fn ($done) => $done->contains('id', $transaction->id));

        $this->assertNotNull($transaction->fresh()->prepared_at);
    }

    public function test_marking_an_order_unprepared_moves_it_back_to_pending(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $transaction = $this->makeTransaction($store, $owner, preparedAt: now());

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('markUnprepared', $transaction->id)
            ->assertViewHas('pending', fn ($pending) => $pending->contains('id', $transaction->id));

        $this->assertNull($transaction->fresh()->prepared_at);
    }

    public function test_clicking_a_card_opens_detail_without_marking_it_prepared(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $transaction = $this->makeTransaction($store, $owner);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('openDetail', $transaction->id)
            ->assertSet('viewingId', $transaction->id)
            ->assertViewHas('viewing', fn ($viewing) => $viewing->id === $transaction->id)
            ->assertViewHas('pending', fn ($pending) => $pending->contains('id', $transaction->id));

        $this->assertNull($transaction->fresh()->prepared_at);
    }

    public function test_marking_prepared_while_viewing_closes_the_detail_modal(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $transaction = $this->makeTransaction($store, $owner);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('openDetail', $transaction->id)
            ->call('markPrepared', $transaction->id)
            ->assertSet('viewingId', null);
    }

    public function test_orders_from_another_store_never_appear(): void
    {
        $storeA = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $storeB = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $ownerA = User::factory()->create(['store_id' => $storeA->id, 'role' => 'owner']);
        $ownerB = User::factory()->create(['store_id' => $storeB->id, 'role' => 'owner']);
        $otherStoreOrder = $this->makeTransaction($storeB, $ownerB);

        Livewire::actingAs($ownerA)
            ->test(Index::class)
            ->assertViewHas('pending', fn ($pending) => ! $pending->contains('id', $otherStoreOrder->id));
    }
}
