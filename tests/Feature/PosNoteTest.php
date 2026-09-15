<?php

namespace Tests\Feature;

use App\Livewire\Pos\Terminal;
use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_name_and_order_note_are_saved_to_the_transaction(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Nasi Goreng',
            'price' => 20000,
            'cost_price' => 12000,
            'stock_qty' => 10,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->set('customerName', 'Budi')
            ->set('orderNote', 'Dibungkus terpisah')
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', '20000')
            ->call('checkout')
            ->assertHasNoErrors();

        $transaction = Transaction::firstOrFail();

        $this->assertSame('Budi', $transaction->customer_name);
        $this->assertSame('Dibungkus terpisah', $transaction->note);
    }

    public function test_a_new_transaction_resets_customer_name_and_order_note(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Nasi Goreng',
            'price' => 20000,
            'cost_price' => 12000,
            'stock_qty' => 10,
        ]);

        $component = Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->set('customerName', 'Budi')
            ->set('orderNote', 'Dibungkus terpisah')
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', '20000')
            ->call('checkout')
            ->call('newTransaction');

        $component->assertSet('customerName', '')->assertSet('orderNote', '');
    }

    public function test_note_on_a_cart_item_is_saved_to_the_transaction_item(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Nasi Goreng',
            'price' => 20000,
            'cost_price' => 12000,
            'stock_qty' => 10,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->set("cart.{$product->id}.note", 'Tanpa pedas, telur mata sapi')
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', '20000')
            ->call('checkout')
            ->assertHasNoErrors();

        $item = TransactionItem::where('product_id', $product->id)->firstOrFail();

        $this->assertSame('Tanpa pedas, telur mata sapi', $item->note);
    }

    public function test_empty_note_is_stored_as_null(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Es Teh',
            'price' => 5000,
            'cost_price' => 2000,
            'stock_qty' => 10,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', '5000')
            ->call('checkout')
            ->assertHasNoErrors();

        $item = TransactionItem::where('product_id', $product->id)->firstOrFail();

        $this->assertNull($item->note);
    }
}
