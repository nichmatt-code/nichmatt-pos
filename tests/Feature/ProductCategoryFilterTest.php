<?php

namespace Tests\Feature;

use App\Livewire\Pos\Terminal;
use App\Livewire\SelfOrder\Menu;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCategoryFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_terminal_filters_products_by_selected_category(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        $makanan = Category::create(['store_id' => $store->id, 'name' => 'Makanan']);
        $minuman = Category::create(['store_id' => $store->id, 'name' => 'Minuman']);
        Product::create(['store_id' => $store->id, 'category_id' => $makanan->id, 'name' => 'Nasi Goreng', 'price' => 20000, 'cost_price' => 12000, 'stock_qty' => 10]);
        Product::create(['store_id' => $store->id, 'category_id' => $minuman->id, 'name' => 'Es Teh', 'price' => 5000, 'cost_price' => 2000, 'stock_qty' => 10]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('selectCategory', $makanan->id)
            ->assertSee('Nasi Goreng')
            ->assertDontSee('Es Teh');
    }

    public function test_pos_terminal_groups_products_by_category_when_no_category_is_selected(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        $makanan = Category::create(['store_id' => $store->id, 'name' => 'Makanan']);
        $minuman = Category::create(['store_id' => $store->id, 'name' => 'Minuman']);
        Product::create(['store_id' => $store->id, 'category_id' => $makanan->id, 'name' => 'Nasi Goreng', 'price' => 20000, 'cost_price' => 12000, 'stock_qty' => 10]);
        Product::create(['store_id' => $store->id, 'category_id' => $minuman->id, 'name' => 'Es Teh', 'price' => 5000, 'cost_price' => 2000, 'stock_qty' => 10]);
        Product::create(['store_id' => $store->id, 'name' => 'Barang Lain', 'price' => 1000, 'cost_price' => 500, 'stock_qty' => 10]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->assertSeeInOrder(['Makanan', 'Nasi Goreng', 'Minuman', 'Es Teh', 'Tanpa Kategori', 'Barang Lain']);
    }

    public function test_pos_terminal_category_filter_is_isolated_per_store(): void
    {
        $storeOne = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $storeTwo = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $storeOne->id, 'role' => 'kasir']);
        Category::create(['store_id' => $storeTwo->id, 'name' => 'Kategori Toko Lain']);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->assertDontSee('Kategori Toko Lain');
    }

    public function test_self_order_menu_filters_products_by_selected_category_for_guests(): void
    {
        $store = Store::factory()->create();

        $makanan = Category::create(['store_id' => $store->id, 'name' => 'Makanan']);
        $minuman = Category::create(['store_id' => $store->id, 'name' => 'Minuman']);
        Product::create(['store_id' => $store->id, 'category_id' => $makanan->id, 'name' => 'Nasi Goreng', 'price' => 20000, 'cost_price' => 12000, 'stock_qty' => 10]);
        Product::create(['store_id' => $store->id, 'category_id' => $minuman->id, 'name' => 'Es Teh', 'price' => 5000, 'cost_price' => 2000, 'stock_qty' => 10]);

        Livewire::test(Menu::class, ['store' => $store])
            ->call('selectCategory', $makanan->id)
            ->assertSee('Nasi Goreng')
            ->assertDontSee('Es Teh');
    }

    public function test_self_order_menu_groups_products_by_category_when_no_category_is_selected(): void
    {
        $store = Store::factory()->create();

        $makanan = Category::create(['store_id' => $store->id, 'name' => 'Makanan']);
        $minuman = Category::create(['store_id' => $store->id, 'name' => 'Minuman']);
        Product::create(['store_id' => $store->id, 'category_id' => $makanan->id, 'name' => 'Nasi Goreng', 'price' => 20000, 'cost_price' => 12000, 'stock_qty' => 10]);
        Product::create(['store_id' => $store->id, 'category_id' => $minuman->id, 'name' => 'Es Teh', 'price' => 5000, 'cost_price' => 2000, 'stock_qty' => 10]);

        Livewire::test(Menu::class, ['store' => $store])
            ->assertSeeInOrder(['Makanan', 'Nasi Goreng', 'Minuman', 'Es Teh']);
    }
}
