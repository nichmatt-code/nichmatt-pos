<?php

namespace Tests\Feature;

use App\Livewire\SelfOrder\Menu;
use App\Models\Product;
use App\Models\SelfOrder;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ApiSelfOrderTest extends TestCase
{
    use RefreshDatabase;

    private function createPendingSelfOrder(Store $store, Product $product): string
    {
        $menu = Livewire::test(Menu::class, ['store' => $store])
            ->call('openProductModal', $product->id)
            ->call('confirmAddToCart')
            ->set('customerName', 'Budi')
            ->set('orderNote', 'Meja 5')
            ->call('confirmOrder');

        return $menu->get('confirmedCode');
    }

    public function test_a_cashier_can_claim_a_pending_code_and_gets_its_items_back(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Kopi Susu',
            'price' => 18000,
            'cost_price' => 10000,
            'stock_qty' => 20,
        ]);
        $token = $cashier->createToken('test')->plainTextToken;

        $code = $this->createPendingSelfOrder($store, $product);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/self-orders/claim', ['code' => $code]);

        $response->assertOk()
            ->assertJsonPath('data.customer_name', 'Budi')
            ->assertJsonPath('data.note', 'Meja 5')
            ->assertJsonPath('data.items.0.product_id', $product->id)
            ->assertJsonPath('data.items.0.qty', 1)
            ->assertJsonPath('data.skipped', []);

        $selfOrder = SelfOrder::where('code', $code)->firstOrFail();
        $this->assertSame('claimed', $selfOrder->status);
        $this->assertSame($cashier->id, $selfOrder->claimed_by);
    }

    public function test_claiming_an_unknown_code_fails(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $token = $cashier->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/self-orders/claim', ['code' => 'ZZZZZZ'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_a_code_cannot_be_claimed_twice(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Kopi Susu',
            'price' => 18000,
            'cost_price' => 10000,
            'stock_qty' => 20,
        ]);
        $token = $cashier->createToken('test')->plainTextToken;

        $code = $this->createPendingSelfOrder($store, $product);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/self-orders/claim', ['code' => $code])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/self-orders/claim', ['code' => $code])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_a_cashier_cannot_claim_a_code_from_another_store(): void
    {
        $storeA = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $storeB = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashierA = User::factory()->create(['store_id' => $storeA->id, 'role' => 'kasir']);
        $productB = Product::create([
            'store_id' => $storeB->id,
            'name' => 'Produk B',
            'price' => 1000,
            'cost_price' => 500,
            'stock_qty' => 5,
        ]);
        $token = $cashierA->createToken('test')->plainTextToken;

        $code = $this->createPendingSelfOrder($storeB, $productB);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/self-orders/claim', ['code' => $code])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_checking_out_a_claimed_self_order_links_and_completes_it(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Kopi Susu',
            'price' => 18000,
            'cost_price' => 10000,
            'stock_qty' => 20,
        ]);
        $token = $cashier->createToken('test')->plainTextToken;

        $code = $this->createPendingSelfOrder($store, $product);

        $claim = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/self-orders/claim', ['code' => $code])
            ->json('data');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/transactions', [
                'items' => [['product_id' => $product->id, 'qty' => 1]],
                'self_order_id' => $claim['self_order_id'],
                'payment_method' => 'cash',
                'paid_amount' => 18000,
            ]);

        $response->assertCreated();

        $selfOrder = SelfOrder::findOrFail($claim['self_order_id']);
        $this->assertSame('completed', $selfOrder->fresh()->status);

        $transaction = Transaction::where('self_order_id', $selfOrder->id)->first();
        $this->assertNotNull($transaction);
        $this->assertSame($response->json('data.id'), $transaction->id);
    }

    public function test_a_self_order_claimed_by_someone_else_cannot_be_used_at_checkout(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashierA = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $cashierB = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Kopi Susu',
            'price' => 18000,
            'cost_price' => 10000,
            'stock_qty' => 20,
        ]);

        $code = $this->createPendingSelfOrder($store, $product);

        $claim = $this->withHeader('Authorization', 'Bearer '.$cashierA->createToken('a')->plainTextToken)
            ->postJson('/api/v1/self-orders/claim', ['code' => $code])
            ->json('data');

        // Setiap request lewat `withHeader('Authorization', ...)` di sini
        // berjalan dalam SATU proses PHPUnit yang sama, dan guard Sanctum
        // suka menyimpan cache user yang berhasil diautentikasi di request
        // sebelumnya. `forgetGuards()` memaksa request berikutnya
        // benar-benar login ulang sebagai cashierB, bukan "kebagian" sesi
        // cashierA - ini murni kebutuhan testing, di dunia nyata setiap
        // request HTTP memang selalu proses baru.
        $this->app->make('auth')->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$cashierB->createToken('b')->plainTextToken)
            ->postJson('/api/v1/transactions', [
                'items' => [['product_id' => $product->id, 'qty' => 1]],
                'self_order_id' => $claim['self_order_id'],
                'payment_method' => 'cash',
                'paid_amount' => 18000,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('self_order_id');
    }
}
