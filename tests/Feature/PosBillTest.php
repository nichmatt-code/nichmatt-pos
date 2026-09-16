<?php

namespace Tests\Feature;

use App\Livewire\Pos\Terminal;
use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosBillTest extends TestCase
{
    use RefreshDatabase;

    public function test_printing_a_bill_with_an_empty_cart_shows_an_error(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('printBill')
            ->assertHasErrors('cart');

        $this->assertNull(session('pos_bill'));
    }

    public function test_printing_a_bill_stashes_the_cart_in_session_without_creating_a_transaction(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Kopi Susu', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 20]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->set('customerName', 'Budi')
            ->set('discount', '2000')
            ->call('printBill')
            ->assertHasNoErrors()
            ->assertDispatched('bill-ready');

        $bill = session('pos_bill');
        $this->assertNotNull($bill);
        $this->assertSame($store->id, $bill['store_id']);
        $this->assertSame('Budi', $bill['customer_name']);
        $this->assertSame(18000, $bill['subtotal']);
        $this->assertSame(2000, $bill['discount']);
        $this->assertSame(16000, $bill['total']);
        $this->assertCount(1, $bill['items']);

        $this->assertSame(0, Transaction::withoutGlobalScopes()->where('store_id', $store->id)->count());
        $this->assertSame(20, $product->fresh()->stock_qty);
    }

    public function test_bill_page_renders_the_stashed_cart(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Kopi Susu', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 20]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->call('printBill');

        $this->actingAs($cashier)
            ->get('/pos/bill')
            ->assertOk()
            ->assertSee('BILL SEMENTARA')
            ->assertSee('Kopi Susu');
    }

    public function test_bill_page_404s_when_nothing_has_been_staged(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        $this->actingAs($cashier)->get('/pos/bill')->assertNotFound();
    }

    public function test_bill_page_404s_for_a_bill_staged_by_a_different_store(): void
    {
        $storeOne = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $storeTwo = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashierOne = User::factory()->create(['store_id' => $storeOne->id, 'role' => 'kasir']);
        $cashierTwo = User::factory()->create(['store_id' => $storeTwo->id, 'role' => 'kasir']);
        $product = Product::create(['store_id' => $storeTwo->id, 'name' => 'Kopi Susu', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 20]);

        Livewire::actingAs($cashierTwo)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->call('printBill');

        $this->actingAs($cashierOne)->get('/pos/bill')->assertNotFound();
    }
}
