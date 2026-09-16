<?php

namespace Tests\Feature;

use App\Livewire\Pos\Terminal;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosProductModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_a_product_shows_its_description(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Nasi Goreng',
            'description' => 'Nasi goreng dengan telur mata sapi dan kerupuk.',
            'price' => 20000,
            'cost_price' => 10000,
            'stock_qty' => 10,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('openProductModal', $product->id)
            ->assertSet('viewingProductId', $product->id)
            ->assertSet('modalQty', 1)
            ->assertSee('Nasi goreng dengan telur mata sapi dan kerupuk.');
    }

    public function test_confirming_the_modal_adds_the_chosen_quantity_and_notifies(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Nasi Goreng',
            'price' => 20000,
            'cost_price' => 10000,
            'stock_qty' => 10,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('openProductModal', $product->id)
            ->call('incrementModalQty')
            ->call('incrementModalQty')
            ->assertSet('modalQty', 3)
            ->call('confirmAddToCart')
            ->assertSet('viewingProductId', null)
            ->assertDispatched('product-added')
            ->assertSet('cart.'.$product->id.'.qty', 3);
    }

    public function test_modal_quantity_is_capped_by_available_stock(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Nasi Goreng',
            'price' => 20000,
            'cost_price' => 10000,
            'stock_qty' => 2,
        ]);

        $component = Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('openProductModal', $product->id);

        for ($i = 0; $i < 5; $i++) {
            $component->call('incrementModalQty');
        }

        $component->assertSet('modalQty', 2);
    }

    public function test_opening_an_out_of_stock_product_shows_an_error_instead_of_the_modal(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Nasi Goreng',
            'price' => 20000,
            'cost_price' => 10000,
            'stock_qty' => 0,
            'is_out_of_stock' => true,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('openProductModal', $product->id)
            ->assertSet('viewingProductId', null)
            ->assertHasErrors('cart');
    }
}
