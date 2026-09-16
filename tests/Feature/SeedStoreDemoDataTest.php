<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedStoreDemoDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fails_gracefully_for_an_unknown_email(): void
    {
        $this->artisan('store:seed-demo-data', ['email' => 'tidak-ada@example.com', '--force' => true])
            ->assertExitCode(1);
    }

    public function test_it_seeds_a_full_restaurant_demo_dataset_for_the_stores_owner(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner', 'email' => 'demo-owner@example.com']);

        $this->artisan('store:seed-demo-data', ['email' => 'demo-owner@example.com', '--force' => true])
            ->assertExitCode(0);

        $this->assertSame(4, Category::withoutGlobalScopes()->where('store_id', $store->id)->count());
        $this->assertSame(4, Tag::withoutGlobalScopes()->where('store_id', $store->id)->count());
        $this->assertSame(16, Product::withoutGlobalScopes()->where('store_id', $store->id)->count());
        $this->assertSame(8, InventoryItem::withoutGlobalScopes()->where('store_id', $store->id)->count());
        $this->assertGreaterThan(0, Transaction::withoutGlobalScopes()->where('store_id', $store->id)->count());

        $unlimited = Product::withoutGlobalScopes()->where('store_id', $store->id)->where('is_unlimited_stock', true)->first();
        $outOfStock = Product::withoutGlobalScopes()->where('store_id', $store->id)->where('is_out_of_stock', true)->first();
        $this->assertNotNull($unlimited);
        $this->assertNotNull($outOfStock);
    }

    public function test_it_wipes_existing_data_for_the_store_before_reseeding(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner', 'email' => 'demo-owner2@example.com']);
        $staleCategory = Category::create(['store_id' => $store->id, 'name' => 'Kategori Lama']);
        Product::create(['store_id' => $store->id, 'category_id' => $staleCategory->id, 'name' => 'Produk Lama', 'price' => 1000, 'cost_price' => 500, 'stock_qty' => 1]);

        $this->artisan('store:seed-demo-data', ['email' => 'demo-owner2@example.com', '--force' => true])
            ->assertExitCode(0);

        $this->assertModelMissing($staleCategory);
        $this->assertSame(0, Product::withoutGlobalScopes()->where('store_id', $store->id)->where('name', 'Produk Lama')->count());
        $this->assertSame(4, Category::withoutGlobalScopes()->where('store_id', $store->id)->count());
    }

    public function test_it_does_not_touch_another_stores_data(): void
    {
        $storeOne = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $storeTwo = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        User::factory()->create(['store_id' => $storeOne->id, 'role' => 'owner', 'email' => 'demo-owner3@example.com']);
        Category::create(['store_id' => $storeTwo->id, 'name' => 'Punya Toko Lain']);

        $this->artisan('store:seed-demo-data', ['email' => 'demo-owner3@example.com', '--force' => true])
            ->assertExitCode(0);

        $this->assertSame(1, Category::withoutGlobalScopes()->where('store_id', $storeTwo->id)->count());
        $this->assertSame('Punya Toko Lain', Category::withoutGlobalScopes()->where('store_id', $storeTwo->id)->first()->name);
    }

    public function test_it_never_deletes_employee_accounts(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner', 'email' => 'demo-owner4@example.com']);
        $kasir = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        $this->artisan('store:seed-demo-data', ['email' => 'demo-owner4@example.com', '--force' => true])
            ->assertExitCode(0);

        $this->assertModelExists($owner);
        $this->assertModelExists($kasir);
    }

    public function test_declining_the_confirmation_leaves_data_untouched(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        User::factory()->create(['store_id' => $store->id, 'role' => 'owner', 'email' => 'demo-owner5@example.com']);
        $staleCategory = Category::create(['store_id' => $store->id, 'name' => 'Kategori Lama']);

        $this->artisan('store:seed-demo-data', ['email' => 'demo-owner5@example.com'])
            ->expectsConfirmation('Lanjutkan?', 'no')
            ->assertExitCode(0);

        $this->assertModelExists($staleCategory);
        $this->assertSame(0, Product::withoutGlobalScopes()->where('store_id', $store->id)->count());
    }
}
