<?php

namespace Tests\Feature;

use App\Livewire\Products\Index;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_upload_an_image_when_creating_a_product(): void
    {
        Storage::fake('public');

        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('createProduct')
            ->set('name', 'Kopi Susu')
            ->set('price', 18000)
            ->set('cost_price', 10000)
            ->set('stock_qty', 20)
            ->set('image', UploadedFile::fake()->image('kopi.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::where('name', 'Kopi Susu')->firstOrFail();

        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
        $this->assertNotNull($product->imageUrl());
    }

    public function test_removing_the_image_clears_it_from_storage(): void
    {
        Storage::fake('public');

        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $path = UploadedFile::fake()->image('produk.jpg')->store('products', 'public');
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Teh Botol',
            'price' => 5000,
            'cost_price' => 3000,
            'stock_qty' => 10,
            'image_path' => $path,
        ]);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('editProduct', $product->id)
            ->call('removeImage')
            ->call('save')
            ->assertHasNoErrors();

        Storage::disk('public')->assertMissing($path);
        $this->assertNull($product->fresh()->image_path);
    }
}
