<?php

namespace Tests\Feature;

use App\Livewire\Pos\Terminal;
use App\Models\Product;
use App\Models\Promo;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PromoCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_product_with_an_active_discount_is_added_to_the_cart_at_its_discounted_price(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Es Teh', 'price' => 5000, 'cost_price' => 2000, 'stock_qty' => 20]);

        Promo::create([
            'store_id' => $store->id,
            'name' => 'Diskon Es Teh',
            'type' => 'discount',
            'product_id' => $product->id,
            'discount_type' => 'percent',
            'discount_value' => 20,
            'is_active' => true,
        ]);

        $component = Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id);

        $this->assertSame(4000, $component->get('cart')[$product->id]['price']);
    }

    public function test_checkout_charges_the_discounted_price_even_when_price_edit_is_disabled(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10), 'allow_price_edit' => false]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Es Teh', 'price' => 5000, 'cost_price' => 2000, 'stock_qty' => 20]);

        Promo::create([
            'store_id' => $store->id,
            'name' => 'Diskon Es Teh',
            'type' => 'discount',
            'product_id' => $product->id,
            'discount_type' => 'fixed',
            'discount_value' => 1000,
            'is_active' => true,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->set('paymentMethod', 'qris')
            ->call('checkout')
            ->assertHasNoErrors();

        $transaction = Transaction::first();
        $this->assertSame(4000, $transaction->total);
    }

    public function test_buying_enough_of_the_trigger_product_automatically_adds_the_free_gift(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $burger = Product::create(['store_id' => $store->id, 'name' => 'Burger', 'price' => 25000, 'cost_price' => 12000, 'stock_qty' => 20]);
        $fries = Product::create(['store_id' => $store->id, 'name' => 'Kentang Goreng', 'price' => 10000, 'cost_price' => 4000, 'stock_qty' => 20]);

        Promo::create([
            'store_id' => $store->id,
            'name' => 'Beli 2 Burger Gratis Kentang',
            'type' => 'gift',
            'product_id' => $burger->id,
            'min_qty' => 2,
            'gift_product_id' => $fries->id,
            'gift_qty' => 1,
            'is_active' => true,
        ]);

        $component = Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $burger->id);

        // Only 1 burger so far - gift not earned yet.
        $this->assertArrayNotHasKey('gift_'.Promo::first()->id, $component->get('cart'));

        $component->call('addToCart', $burger->id);
        $promo = Promo::first();
        $cart = $component->get('cart');

        $this->assertArrayHasKey('gift_'.$promo->id, $cart);
        $this->assertSame(0, $cart['gift_'.$promo->id]['price']);
        $this->assertSame(1, $cart['gift_'.$promo->id]['qty']);

        $component->set('paymentMethod', 'qris')->call('checkout')->assertHasNoErrors();

        $transaction = Transaction::first();
        $this->assertSame(50000, $transaction->total); // 2x burger, gift is free
        $this->assertSame(19, $fries->fresh()->stock_qty); // gift stock still deducted
    }

    public function test_removing_the_trigger_product_removes_the_earned_gift(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $burger = Product::create(['store_id' => $store->id, 'name' => 'Burger', 'price' => 25000, 'cost_price' => 12000, 'stock_qty' => 20]);
        $fries = Product::create(['store_id' => $store->id, 'name' => 'Kentang Goreng', 'price' => 10000, 'cost_price' => 4000, 'stock_qty' => 20]);

        $promo = Promo::create([
            'store_id' => $store->id,
            'name' => 'Beli 2 Burger Gratis Kentang',
            'type' => 'gift',
            'product_id' => $burger->id,
            'min_qty' => 2,
            'gift_product_id' => $fries->id,
            'gift_qty' => 1,
            'is_active' => true,
        ]);

        $component = Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $burger->id)
            ->call('addToCart', $burger->id);

        $this->assertArrayHasKey('gift_'.$promo->id, $component->get('cart'));

        $component->call('decrementQty', (string) $burger->id);

        $this->assertArrayNotHasKey('gift_'.$promo->id, $component->get('cart'));
    }
}
