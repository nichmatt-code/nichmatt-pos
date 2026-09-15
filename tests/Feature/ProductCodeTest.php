<?php

namespace Tests\Feature;

use App\Livewire\Pos\Terminal;
use App\Livewire\Products\Index;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_auto_generate_sku_and_barcode_when_creating_a_product(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('createProduct')
            ->call('generateSku')
            ->call('generateBarcode')
            ->set('name', 'Kopi Susu')
            ->set('price', 18000)
            ->set('cost_price', 10000)
            ->set('stock_qty', 10)
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::where('name', 'Kopi Susu')->firstOrFail();

        $this->assertMatchesRegularExpression('/^SKU-[A-Z0-9]{6}$/', $product->sku);
        $this->assertMatchesRegularExpression('/^\d{12}$/', $product->barcode);
    }

    public function test_sku_can_still_be_typed_in_manually(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('createProduct')
            ->set('sku', 'CUSTOM-001')
            ->set('name', 'Teh Botol')
            ->set('price', 5000)
            ->set('cost_price', 3000)
            ->set('stock_qty', 10)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('CUSTOM-001', Product::where('name', 'Teh Botol')->value('sku'));
    }

    public function test_duplicate_barcode_within_the_same_store_is_rejected(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        Product::create(['store_id' => $store->id, 'name' => 'Produk A', 'price' => 1000, 'cost_price' => 500, 'stock_qty' => 5, 'barcode' => '111111111111']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('createProduct')
            ->set('barcode', '111111111111')
            ->set('name', 'Produk B')
            ->set('price', 1000)
            ->set('cost_price', 500)
            ->set('stock_qty', 5)
            ->call('save')
            ->assertHasErrors('barcode');
    }

    public function test_the_same_barcode_can_be_used_by_different_stores(): void
    {
        $storeA = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $storeB = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $ownerB = User::factory()->create(['store_id' => $storeB->id, 'role' => 'owner']);
        Product::create(['store_id' => $storeA->id, 'name' => 'Produk A', 'price' => 1000, 'cost_price' => 500, 'stock_qty' => 5, 'barcode' => '222222222222']);

        Livewire::actingAs($ownerB)
            ->test(Index::class)
            ->call('createProduct')
            ->set('barcode', '222222222222')
            ->set('name', 'Produk B')
            ->set('price', 1000)
            ->set('cost_price', 500)
            ->set('stock_qty', 5)
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_cashier_can_scan_a_barcode_to_add_the_product_to_cart(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Kopi Susu',
            'price' => 18000,
            'cost_price' => 10000,
            'stock_qty' => 10,
            'barcode' => '333333333333',
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->set('barcodeInput', '333333333333')
            ->call('scanBarcode')
            ->assertHasNoErrors()
            ->assertSet('cart.'.$product->id.'.qty', 1);
    }

    public function test_scanning_an_unknown_barcode_shows_an_error(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->set('barcodeInput', '000000000000')
            ->call('scanBarcode')
            ->assertHasErrors('barcodeInput');
    }

    public function test_label_page_requires_a_barcode_to_exist(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Tanpa Barcode', 'price' => 1000, 'cost_price' => 500, 'stock_qty' => 5]);

        $this->actingAs($owner)->get("/products/{$product->id}/label")->assertNotFound();
    }

    public function test_label_page_renders_a_barcode_for_a_product_that_has_one(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Kopi Susu',
            'price' => 18000,
            'cost_price' => 10000,
            'stock_qty' => 10,
            'barcode' => '444444444444',
        ]);

        $this->actingAs($owner)
            ->get("/products/{$product->id}/label")
            ->assertOk()
            ->assertSee('444444444444')
            ->assertSee('<svg', false);
    }
}
