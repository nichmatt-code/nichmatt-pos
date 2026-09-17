<?php

namespace Tests\Feature;

use App\Livewire\Packages\Index;
use App\Models\Package;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_a_package_with_multiple_products(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $nasiGoreng = Product::create(['store_id' => $store->id, 'name' => 'Nasi Goreng', 'price' => 15000, 'cost_price' => 7000, 'stock_qty' => 10]);
        $esTeh = Product::create(['store_id' => $store->id, 'name' => 'Es Teh', 'price' => 5000, 'cost_price' => 2000, 'stock_qty' => 20]);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('createPackage')
            ->set('name', 'Paket Hemat')
            ->set('price', '18000')
            ->call('toggleItem', $nasiGoreng->id)
            ->call('toggleItem', $esTeh->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('packages', 1);
        $package = Package::first();
        $this->assertSame('Paket Hemat', $package->name);
        $this->assertSame(18000, $package->price);
        $this->assertSame(2, $package->items()->count());
    }

    public function test_a_package_requires_at_least_one_product(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('createPackage')
            ->set('name', 'Paket Kosong')
            ->set('price', '10000')
            ->call('save')
            ->assertHasErrors(['itemQty']);

        $this->assertDatabaseCount('packages', 0);
    }

    public function test_max_sellable_is_limited_by_the_scarcest_component(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $plentiful = Product::create(['store_id' => $store->id, 'name' => 'Air Mineral', 'price' => 4000, 'cost_price' => 2000, 'stock_qty' => 100]);
        $scarce = Product::create(['store_id' => $store->id, 'name' => 'Kue Spesial', 'price' => 20000, 'cost_price' => 10000, 'stock_qty' => 3]);

        $package = Package::create(['store_id' => $store->id, 'name' => 'Paket Combo', 'price' => 20000, 'is_active' => true]);
        $package->items()->create(['product_id' => $plentiful->id, 'qty' => 1]);
        $package->items()->create(['product_id' => $scarce->id, 'qty' => 2]);

        $this->assertSame(1, $package->fresh()->load('items.product')->maxSellable());
    }
}
