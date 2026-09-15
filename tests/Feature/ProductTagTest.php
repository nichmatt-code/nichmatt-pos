<?php

namespace Tests\Feature;

use App\Livewire\Pos\Terminal;
use App\Livewire\Products\Index;
use App\Livewire\SelfOrder\Menu;
use App\Models\Product;
use App\Models\Store;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_attach_multiple_tags_to_a_product_via_the_form(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('createProduct')
            ->set('name', 'Nasi Goreng')
            ->set('price', '25000')
            ->set('cost_price', '15000')
            ->set('unit', 'porsi')
            ->set('stock_qty', '10')
            ->set('newTagName', 'Pedas')
            ->call('addTag')
            ->set('newTagName', 'Best Seller')
            ->call('addTag')
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::where('name', 'Nasi Goreng')->firstOrFail();

        $this->assertEqualsCanonicalizing(['Pedas', 'Best Seller'], $product->tags->pluck('name')->all());
    }

    public function test_adding_a_tag_reuses_an_existing_tag_with_the_same_name(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $existingTag = Tag::create(['store_id' => $store->id, 'name' => 'Pedas']);

        $component = Livewire::actingAs($owner)
            ->test(Index::class)
            ->set('newTagName', 'Pedas')
            ->call('addTag');

        $this->assertSame(1, Tag::where('name', 'Pedas')->count());
        $this->assertSame([$existingTag->id], $component->get('tag_ids'));
    }

    public function test_editing_a_product_removes_a_tag_after_it_is_unchecked(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Es Teh',
            'price' => 5000,
            'cost_price' => 2000,
            'stock_qty' => 20,
        ]);
        $tag = Tag::create(['store_id' => $store->id, 'name' => 'Minuman']);
        $product->tags()->attach($tag);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('editProduct', $product->id)
            ->call('removeTag', $tag->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertCount(0, $product->fresh()->tags);
    }

    public function test_tags_are_isolated_per_store(): void
    {
        $storeOne = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $storeTwo = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $ownerOne = User::factory()->create(['store_id' => $storeOne->id, 'role' => 'owner']);
        Tag::create(['store_id' => $storeTwo->id, 'name' => 'Tag Toko Lain']);

        Livewire::actingAs($ownerOne)
            ->test(Index::class)
            ->assertDontSee('Tag Toko Lain');
    }

    public function test_pos_terminal_filters_products_by_selected_tag(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        $spicy = Product::create(['store_id' => $store->id, 'name' => 'Ayam Geprek', 'price' => 20000, 'cost_price' => 12000, 'stock_qty' => 10]);
        $mild = Product::create(['store_id' => $store->id, 'name' => 'Ayam Goreng', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 10]);
        $tag = Tag::create(['store_id' => $store->id, 'name' => 'Pedas']);
        $spicy->tags()->attach($tag);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('toggleTag', $tag->id)
            ->assertSee('Ayam Geprek')
            ->assertDontSee('Ayam Goreng');
    }

    public function test_self_order_menu_filters_products_by_selected_tag_for_guests(): void
    {
        $store = Store::factory()->create();

        $spicy = Product::create(['store_id' => $store->id, 'name' => 'Ayam Geprek', 'price' => 20000, 'cost_price' => 12000, 'stock_qty' => 10]);
        $mild = Product::create(['store_id' => $store->id, 'name' => 'Ayam Goreng', 'price' => 18000, 'cost_price' => 10000, 'stock_qty' => 10]);
        $tag = Tag::create(['store_id' => $store->id, 'name' => 'Pedas']);
        $spicy->tags()->attach($tag);

        Livewire::test(Menu::class, ['store' => $store])
            ->call('toggleTag', $tag->id)
            ->assertSee('Ayam Geprek')
            ->assertDontSee('Ayam Goreng');
    }

    public function test_self_order_menu_only_shows_tags_belonging_to_its_own_store(): void
    {
        $storeOne = Store::factory()->create();
        $storeTwo = Store::factory()->create();
        Tag::create(['store_id' => $storeTwo->id, 'name' => 'Tag Toko Lain']);

        Livewire::test(Menu::class, ['store' => $storeOne])
            ->assertDontSee('Tag Toko Lain');
    }
}
