<?php

namespace Tests\Feature;

use App\Livewire\Branch\Settings;
use App\Livewire\Pos\Terminal;
use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_save_pos_settings(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Settings::class)
            ->set('showProductImages', false)
            ->set('allowPriceEdit', true)
            ->set('taxPercent', '11')
            ->set('serviceChargePercent', '5')
            ->call('savePosSettings')
            ->assertHasNoErrors();

        $store->refresh();
        $this->assertFalse($store->show_product_images);
        $this->assertTrue($store->allow_price_edit);
        $this->assertSame(11, $store->tax_percent);
        $this->assertSame(5, $store->service_charge_percent);
    }

    public function test_checkout_applies_service_charge_then_tax_on_top_of_it(): void
    {
        // Service charge is applied to the Rp 20.000 subtotal first (5% =
        // Rp 1.000), then tax is calculated on top of that Rp 21.000 (10% =
        // Rp 2.100) - the usual F&B order, not two independent percentages
        // of the bare subtotal.
        $store = Store::factory()->create([
            'trial_ends_at' => now()->addDays(10),
            'tax_percent' => 10,
            'service_charge_percent' => 5,
        ]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Kopi Susu',
            'price' => 20000,
            'cost_price' => 10000,
            'stock_qty' => 20,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->assertSet('serviceChargeAmount', 1000)
            ->assertSet('taxAmount', 2100)
            ->assertSet('total', 23100)
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', '23100')
            ->call('checkout')
            ->assertHasNoErrors();

        $transaction = Transaction::firstOrFail();
        $this->assertSame(2100, $transaction->tax_amount);
        $this->assertSame(1000, $transaction->service_charge_amount);
        $this->assertSame(23100, $transaction->total);
    }

    public function test_cashier_can_edit_a_line_item_price_when_the_store_allows_it(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10), 'allow_price_edit' => true]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Kopi Susu',
            'price' => 20000,
            'cost_price' => 10000,
            'stock_qty' => 20,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->set('cart.'.$product->id.'.price', 15000)
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', '15000')
            ->call('checkout')
            ->assertHasNoErrors();

        $transaction = Transaction::firstOrFail();
        $this->assertSame(15000, $transaction->subtotal);
        $this->assertSame(15000, $transaction->total);
    }

    public function test_price_tampering_is_ignored_when_the_store_does_not_allow_price_edit(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10), 'allow_price_edit' => false]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Kopi Susu',
            'price' => 20000,
            'cost_price' => 10000,
            'stock_qty' => 20,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->set('cart.'.$product->id.'.price', 1)
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', '20000')
            ->call('checkout')
            ->assertHasNoErrors();

        $transaction = Transaction::firstOrFail();
        $this->assertSame(20000, $transaction->subtotal);
        $this->assertSame(20000, $transaction->total);
    }

    public function test_pos_hides_product_images_when_disabled(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10), 'show_product_images' => false]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Kopi Susu',
            'price' => 20000,
            'cost_price' => 10000,
            'stock_qty' => 20,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->assertSee('Kopi Susu')
            ->assertDontSeeHtml('alt="'.$product->name.'"');
    }
}
