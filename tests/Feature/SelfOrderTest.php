<?php

namespace Tests\Feature;

use App\Livewire\Pos\Terminal;
use App\Livewire\SelfOrder\Menu;
use App\Models\Product;
use App\Models\SelfOrder;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SelfOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_gets_an_order_token_automatically(): void
    {
        $store = Store::factory()->create();

        $this->assertNotEmpty($store->order_token);
    }

    public function test_the_public_menu_page_loads_without_logging_in(): void
    {
        $store = Store::factory()->create();

        $this->get('/order/'.$store->order_token)->assertOk();
    }

    public function test_guest_can_confirm_a_self_order_and_receives_a_code(): void
    {
        $store = Store::factory()->create();
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Kopi Susu',
            'price' => 18000,
            'cost_price' => 10000,
            'stock_qty' => 20,
        ]);

        $component = Livewire::test(Menu::class, ['store' => $store])
            ->call('openProductModal', $product->id)
            ->call('confirmAddToCart')
            ->set('customerName', 'Budi')
            ->set('orderNote', 'Meja 5')
            ->call('confirmOrder')
            ->assertHasNoErrors();

        $selfOrder = SelfOrder::where('store_id', $store->id)->firstOrFail();

        $this->assertSame('Budi', $selfOrder->customer_name);
        $this->assertSame('Meja 5', $selfOrder->note);
        $this->assertSame(18000, $selfOrder->total);
        $this->assertTrue($selfOrder->isPending());
        $component->assertSet('confirmedCode', $selfOrder->code);
        $component->assertSee('<svg', false);
    }

    public function test_confirming_an_empty_cart_shows_an_error(): void
    {
        $store = Store::factory()->create();

        Livewire::test(Menu::class, ['store' => $store])
            ->call('confirmOrder')
            ->assertHasErrors('cart');

        $this->assertDatabaseCount('self_orders', 0);
    }

    public function test_self_order_menu_only_shows_products_from_its_own_store(): void
    {
        $storeA = Store::factory()->create();
        $storeB = Store::factory()->create();

        Product::create(['store_id' => $storeA->id, 'name' => 'Produk A', 'price' => 1000, 'cost_price' => 500, 'stock_qty' => 5]);
        Product::create(['store_id' => $storeB->id, 'name' => 'Produk B', 'price' => 2000, 'cost_price' => 1000, 'stock_qty' => 5]);

        Livewire::test(Menu::class, ['store' => $storeA])
            ->assertSee('Produk A')
            ->assertDontSee('Produk B');
    }

    public function test_cashier_can_claim_a_pending_code_into_the_pos_cart(): void
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

        $menu = Livewire::test(Menu::class, ['store' => $store])
            ->call('openProductModal', $product->id)
            ->call('confirmAddToCart')
            ->set('customerName', 'Budi')
            ->call('confirmOrder');

        $code = $menu->get('confirmedCode');

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->set('orderCodeInput', $code)
            ->call('claimCode')
            ->assertHasNoErrors()
            ->assertSet('customerName', 'Budi');

        $selfOrder = SelfOrder::where('code', $code)->firstOrFail();
        $this->assertSame('claimed', $selfOrder->status);
        $this->assertSame($cashier->id, $selfOrder->claimed_by);
    }

    public function test_claiming_an_unknown_code_shows_an_error(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->set('orderCodeInput', 'ZZZZZZ')
            ->call('claimCode')
            ->assertHasErrors('orderCodeInput');
    }

    public function test_cashier_cannot_claim_a_code_belonging_to_another_store(): void
    {
        $storeA = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $storeB = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashierA = User::factory()->create(['store_id' => $storeA->id, 'role' => 'kasir']);
        $product = Product::create(['store_id' => $storeB->id, 'name' => 'Produk B', 'price' => 1000, 'cost_price' => 500, 'stock_qty' => 5]);

        $menu = Livewire::test(Menu::class, ['store' => $storeB])
            ->call('openProductModal', $product->id)
            ->call('confirmAddToCart')
            ->call('confirmOrder');

        Livewire::actingAs($cashierA)
            ->test(Terminal::class)
            ->set('orderCodeInput', $menu->get('confirmedCode'))
            ->call('claimCode')
            ->assertHasErrors('orderCodeInput');
    }

    public function test_checking_out_a_claimed_order_links_the_transaction_and_completes_it(): void
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

        $menu = Livewire::test(Menu::class, ['store' => $store])
            ->call('openProductModal', $product->id)
            ->call('confirmAddToCart')
            ->call('confirmOrder');

        $code = $menu->get('confirmedCode');

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->set('orderCodeInput', $code)
            ->call('claimCode')
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', '18000')
            ->call('checkout')
            ->assertHasNoErrors();

        $selfOrder = SelfOrder::where('code', $code)->firstOrFail();
        $transaction = Transaction::where('self_order_id', $selfOrder->id)->first();

        $this->assertNotNull($transaction);
        $this->assertSame('completed', $selfOrder->fresh()->status);
    }
}
