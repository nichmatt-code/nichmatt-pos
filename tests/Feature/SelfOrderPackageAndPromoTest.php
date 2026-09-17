<?php

namespace Tests\Feature;

use App\Livewire\Pos\Terminal;
use App\Livewire\SelfOrder\Menu;
use App\Models\Package;
use App\Models\Product;
use App\Models\Promo;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SelfOrderPackageAndPromoTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_package_ordered_via_self_order_survives_the_cashiers_claim_and_checkout(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $nasiGoreng = Product::create(['store_id' => $store->id, 'name' => 'Nasi Goreng', 'price' => 15000, 'cost_price' => 7000, 'stock_qty' => 10]);
        $esTeh = Product::create(['store_id' => $store->id, 'name' => 'Es Teh', 'price' => 5000, 'cost_price' => 2000, 'stock_qty' => 20]);

        $package = Package::create(['store_id' => $store->id, 'name' => 'Paket Hemat', 'price' => 18000, 'is_active' => true]);
        $package->items()->create(['product_id' => $nasiGoreng->id, 'qty' => 1]);
        $package->items()->create(['product_id' => $esTeh->id, 'qty' => 1]);

        $order = Livewire::test(Menu::class, ['store' => $store])
            ->call('addPackageToCart', $package->id)
            ->call('confirmOrder');

        $code = $order->get('confirmedCode');
        $this->assertNotNull($code);
        $this->assertDatabaseCount('self_order_items', 1);

        $component = Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->set('orderCodeInput', $code)
            ->call('claimCode')
            ->assertHasNoErrors();

        $cart = $component->get('cart');
        $this->assertArrayHasKey('pkg_'.$package->id, $cart);

        $component->set('paymentMethod', 'cash')->set('paidAmount', '20000')->call('checkout');

        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame(18000, Transaction::first()->total);
        $this->assertSame(9, $nasiGoreng->fresh()->stock_qty);
        $this->assertSame(19, $esTeh->fresh()->stock_qty);
    }

    public function test_a_gift_earned_in_self_order_is_not_charged_when_claimed_by_the_cashier(): void
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

        $order = Livewire::test(Menu::class, ['store' => $store])
            ->call('openProductModal', $burger->id)
            ->call('confirmAddToCart', 2)
            ->call('confirmOrder');

        // The self-order itself shouldn't persist the free gift line - only
        // the paid burgers - since the gift is re-derived at claim time.
        $this->assertDatabaseCount('self_order_items', 1);
        $this->assertSame(50000, $order->get('confirmedTotal'));

        $code = $order->get('confirmedCode');

        $component = Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->set('orderCodeInput', $code)
            ->call('claimCode');

        $promo = Promo::first();
        $cart = $component->get('cart');
        $this->assertArrayHasKey('gift_'.$promo->id, $cart);
        $this->assertSame(0, $cart['gift_'.$promo->id]['price']);

        $component->set('paymentMethod', 'cash')->set('paidAmount', '50000')->call('checkout')->assertHasNoErrors();

        $this->assertSame(50000, Transaction::first()->total);
        $this->assertSame(19, $fries->fresh()->stock_qty);
    }
}
