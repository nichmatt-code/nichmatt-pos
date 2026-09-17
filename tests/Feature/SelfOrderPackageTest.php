<?php

namespace Tests\Feature;

use App\Livewire\Pos\Terminal;
use App\Livewire\SelfOrder\Menu;
use App\Models\Package;
use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SelfOrderPackageTest extends TestCase
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
}
