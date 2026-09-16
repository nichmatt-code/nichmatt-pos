<?php

namespace Tests\Feature;

use App\Livewire\Pos\Terminal;
use App\Livewire\Products\Index;
use App\Livewire\Reports\Sales;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductIngredientTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_attach_ingredients_with_quantities_to_a_product(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $ayam = InventoryItem::create(['store_id' => $store->id, 'name' => 'Ayam Potong', 'unit' => 'pcs', 'stock_qty' => 50, 'min_stock' => 5]);
        $nasi = InventoryItem::create(['store_id' => $store->id, 'name' => 'Beras', 'unit' => 'gram', 'stock_qty' => 5000, 'min_stock' => 500]);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('createProduct')
            ->set('name', 'Ayam Geprek')
            ->set('price', '20000')
            ->set('cost_price', '12000')
            ->set('unit', 'porsi')
            ->set('stock_qty', '10')
            ->call('toggleIngredient', $ayam->id)
            ->set("ingredientQty.{$ayam->id}", '1')
            ->call('toggleIngredient', $nasi->id)
            ->set("ingredientQty.{$nasi->id}", '150')
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::where('name', 'Ayam Geprek')->firstOrFail();
        $ingredients = $product->ingredients()->get()->keyBy('id');

        $this->assertCount(2, $ingredients);
        $this->assertSame(1, (int) $ingredients[$ayam->id]->pivot->qty_used);
        $this->assertSame(150, (int) $ingredients[$nasi->id]->pivot->qty_used);
    }

    public function test_removing_an_ingredient_while_editing_clears_it_from_the_recipe(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $item = InventoryItem::create(['store_id' => $store->id, 'name' => 'Minyak Goreng', 'unit' => 'ml', 'stock_qty' => 5000, 'min_stock' => 500]);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Pisang Goreng', 'price' => 10000, 'cost_price' => 5000, 'stock_qty' => 20]);
        $product->ingredients()->attach($item->id, ['qty_used' => 50]);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('editProduct', $product->id)
            ->assertSet("ingredientQty.{$item->id}", '50')
            ->call('toggleIngredient', $item->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertCount(0, $product->fresh()->ingredients);
    }

    public function test_checkout_deducts_ingredient_stock_proportionally_to_qty_sold(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $ayam = InventoryItem::create(['store_id' => $store->id, 'name' => 'Ayam Potong', 'unit' => 'pcs', 'stock_qty' => 50, 'min_stock' => 5]);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Ayam Geprek', 'price' => 20000, 'cost_price' => 12000, 'stock_qty' => 10]);
        $product->ingredients()->attach($ayam->id, ['qty_used' => 2]);

        $component = Livewire::actingAs($cashier)->test(Terminal::class);
        $component->call('addToCart', $product->id);
        $component->call('incrementQty', $product->id);
        $component->call('incrementQty', $product->id);

        $component
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', '60000')
            ->call('checkout')
            ->assertHasNoErrors();

        // 3 units sold * 2 ayam per unit = 6 consumed.
        $this->assertSame(44, $ayam->fresh()->stock_qty);
        $movement = $ayam->movements()->latest()->first();
        $this->assertNotNull($movement);
        $this->assertSame('out', $movement->type);
        $this->assertSame(-6, $movement->qty);
    }

    public function test_checkout_deducts_ingredients_even_for_an_unlimited_stock_product(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $cup = InventoryItem::create(['store_id' => $store->id, 'name' => 'Cup Plastik', 'unit' => 'pcs', 'stock_qty' => 100, 'min_stock' => 10]);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Air Mineral', 'price' => 4000, 'cost_price' => 2000, 'stock_qty' => 0, 'is_unlimited_stock' => true]);
        $product->ingredients()->attach($cup->id, ['qty_used' => 1]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', '4000')
            ->call('checkout')
            ->assertHasNoErrors();

        $this->assertSame(0, $product->fresh()->stock_qty);
        $this->assertSame(99, $cup->fresh()->stock_qty);
    }

    public function test_checkout_deducts_multiple_ingredients_for_the_same_product(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $ayam = InventoryItem::create(['store_id' => $store->id, 'name' => 'Ayam Potong', 'unit' => 'pcs', 'stock_qty' => 50, 'min_stock' => 5]);
        $beras = InventoryItem::create(['store_id' => $store->id, 'name' => 'Beras', 'unit' => 'gram', 'stock_qty' => 5000, 'min_stock' => 500]);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Ayam Geprek', 'price' => 20000, 'cost_price' => 12000, 'stock_qty' => 10]);
        $product->ingredients()->attach([
            $ayam->id => ['qty_used' => 1],
            $beras->id => ['qty_used' => 150],
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', '20000')
            ->call('checkout')
            ->assertHasNoErrors();

        $this->assertSame(49, $ayam->fresh()->stock_qty);
        $this->assertSame(4850, $beras->fresh()->stock_qty);
    }

    public function test_sales_report_aggregates_ingredient_usage_from_sales_within_the_date_range(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $ayam = InventoryItem::create(['store_id' => $store->id, 'name' => 'Ayam Potong', 'unit' => 'pcs', 'stock_qty' => 50, 'min_stock' => 5]);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Ayam Geprek', 'price' => 20000, 'cost_price' => 12000, 'stock_qty' => 10]);
        $product->ingredients()->attach($ayam->id, ['qty_used' => 2]);

        Livewire::actingAs($owner)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', '20000')
            ->call('checkout');

        // A manual stock adjustment unrelated to a sale must not be counted.
        $ayam->decrement('stock_qty', 5);
        InventoryMovement::create([
            'inventory_item_id' => $ayam->id,
            'type' => 'adjustment',
            'qty' => -5,
            'note' => 'Stok rusak',
        ]);

        Livewire::actingAs($owner)
            ->test(Sales::class)
            ->assertSee('Ayam Potong')
            ->assertSee('2 pcs');
    }
}
