<?php

namespace Tests\Feature;

use App\Livewire\Pos\Terminal;
use App\Livewire\Products\Index;
use App\Livewire\SelfOrder\Menu;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_toggle_a_product_as_out_of_stock_from_the_list(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Kopi Susu', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 20]);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('toggleOutOfStock', $product->id);

        $this->assertTrue($product->fresh()->is_out_of_stock);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('toggleOutOfStock', $product->id);

        $this->assertFalse($product->fresh()->is_out_of_stock);
    }

    public function test_out_of_stock_product_cannot_be_added_to_cart_even_with_stock_remaining(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Kopi Susu', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 20, 'is_out_of_stock' => true]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->assertHasErrors('cart');
    }

    public function test_unlimited_stock_product_can_be_sold_beyond_its_recorded_stock_qty_without_decrementing(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Air Mineral',
            'price' => 3000,
            'cost_price' => 1500,
            'stock_qty' => 0,
            'is_unlimited_stock' => true,
        ]);

        $component = Livewire::actingAs($cashier)->test(Terminal::class);

        for ($i = 0; $i < 5; $i++) {
            $component->call('addToCart', $product->id);
        }

        $component
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', '15000')
            ->call('checkout')
            ->assertHasNoErrors();

        $this->assertSame(0, $product->fresh()->stock_qty);
        $this->assertSame(0, $product->stockMovements()->count());
    }

    public function test_limited_stock_product_still_decrements_and_logs_a_movement_on_checkout(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Kopi Susu', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 10]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', '18000')
            ->call('checkout')
            ->assertHasNoErrors();

        $this->assertSame(9, $product->fresh()->stock_qty);
        $this->assertSame(1, $product->stockMovements()->count());
    }

    public function test_self_order_menu_hides_out_of_stock_product_from_being_added(): void
    {
        $store = Store::factory()->create();
        $product = Product::create(['store_id' => $store->id, 'name' => 'Kopi Susu', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 20, 'is_out_of_stock' => true]);

        Livewire::test(Menu::class, ['store' => $store])
            ->call('addToCart', $product->id)
            ->assertHasErrors('cart');
    }

    public function test_self_order_menu_allows_adding_unlimited_stock_product_with_zero_stock_qty(): void
    {
        $store = Store::factory()->create();
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Air Mineral',
            'price' => 3000,
            'cost_price' => 1500,
            'stock_qty' => 0,
            'is_unlimited_stock' => true,
        ]);

        Livewire::test(Menu::class, ['store' => $store])
            ->call('addToCart', $product->id)
            ->assertHasNoErrors('cart');
    }

    public function test_low_stock_flag_is_false_for_unlimited_or_out_of_stock_products(): void
    {
        $store = Store::factory()->create();

        $unlimited = Product::create(['store_id' => $store->id, 'name' => 'A', 'price' => 1000, 'cost_price' => 500, 'stock_qty' => 2, 'is_unlimited_stock' => true]);
        $outOfStock = Product::create(['store_id' => $store->id, 'name' => 'B', 'price' => 1000, 'cost_price' => 500, 'stock_qty' => 2, 'is_out_of_stock' => true]);
        $lowStock = Product::create(['store_id' => $store->id, 'name' => 'C', 'price' => 1000, 'cost_price' => 500, 'stock_qty' => 2]);

        $this->assertFalse($unlimited->isLowStock());
        $this->assertFalse($outOfStock->isLowStock());
        $this->assertTrue($lowStock->isLowStock());
    }
}
