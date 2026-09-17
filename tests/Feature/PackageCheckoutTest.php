<?php

namespace Tests\Feature;

use App\Livewire\Pos\Terminal;
use App\Models\InventoryItem;
use App\Models\Package;
use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PackageCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_selling_a_package_creates_one_line_item_and_deducts_every_components_stock(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        $flour = InventoryItem::create(['store_id' => $store->id, 'name' => 'Tepung', 'unit' => 'gr', 'stock_qty' => 1000, 'min_stock' => 100]);

        $nasiGoreng = Product::create(['store_id' => $store->id, 'name' => 'Nasi Goreng', 'price' => 15000, 'cost_price' => 7000, 'stock_qty' => 10]);
        $nasiGoreng->ingredients()->attach($flour->id, ['qty_used' => 50]);
        $esTeh = Product::create(['store_id' => $store->id, 'name' => 'Es Teh', 'price' => 5000, 'cost_price' => 2000, 'stock_qty' => 20]);

        $package = Package::create(['store_id' => $store->id, 'name' => 'Paket Hemat', 'price' => 18000, 'is_active' => true]);
        $package->items()->create(['product_id' => $nasiGoreng->id, 'qty' => 1]);
        $package->items()->create(['product_id' => $esTeh->id, 'qty' => 1]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addPackageToCart', $package->id)
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', '20000')
            ->call('checkout')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('transactions', 1);
        $transaction = Transaction::first();
        $this->assertSame(18000, $transaction->total);

        $this->assertSame(1, $transaction->items()->count());
        $item = $transaction->items()->first();
        $this->assertSame('Paket Hemat', $item->product_name);
        $this->assertNull($item->product_id);
        $this->assertSame($package->id, $item->package_id);

        // Both component products' stock, and the recipe ingredient behind
        // Nasi Goreng, must be deducted even though only one line appears.
        $this->assertSame(9, $nasiGoreng->fresh()->stock_qty);
        $this->assertSame(19, $esTeh->fresh()->stock_qty);
        $this->assertSame(950, $flour->fresh()->stock_qty);
    }

    public function test_a_package_cannot_be_sold_beyond_its_components_stock(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $scarce = Product::create(['store_id' => $store->id, 'name' => 'Kue Spesial', 'price' => 20000, 'cost_price' => 10000, 'stock_qty' => 0]);

        $package = Package::create(['store_id' => $store->id, 'name' => 'Paket Kue', 'price' => 20000, 'is_active' => true]);
        $package->items()->create(['product_id' => $scarce->id, 'qty' => 1]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addPackageToCart', $package->id)
            ->assertHasErrors(['cart'])
            ->assertSet('cart', []);
    }
}
