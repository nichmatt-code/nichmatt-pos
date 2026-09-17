<?php

namespace Tests\Feature;

use App\Livewire\Promos\Index;
use App\Models\Product;
use App\Models\Promo;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PromoTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_a_percent_discount_promo(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Es Teh', 'price' => 5000, 'cost_price' => 2000, 'stock_qty' => 20]);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('createPromo')
            ->set('name', 'Diskon Es Teh')
            ->set('productId', $product->id)
            ->set('discountType', 'percent')
            ->set('discountValue', '20')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('promos', 1);
        $promo = Promo::first();
        $this->assertSame('discount', $promo->type);
        $this->assertSame(4000, $promo->discountedPriceFor(5000));
    }

    public function test_owner_can_create_a_gift_promo(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $burger = Product::create(['store_id' => $store->id, 'name' => 'Burger', 'price' => 25000, 'cost_price' => 12000, 'stock_qty' => 20]);
        $fries = Product::create(['store_id' => $store->id, 'name' => 'Kentang Goreng', 'price' => 10000, 'cost_price' => 4000, 'stock_qty' => 20]);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('createPromo')
            ->set('name', 'Beli Burger Gratis Kentang')
            ->set('type', 'gift')
            ->set('productId', $burger->id)
            ->set('minQty', '2')
            ->set('giftProductId', $fries->id)
            ->set('giftQty', '1')
            ->call('save')
            ->assertHasNoErrors();

        $promo = Promo::first();
        $this->assertSame('gift', $promo->type);
        $this->assertSame(2, $promo->min_qty);
        $this->assertSame($fries->id, $promo->gift_product_id);
    }

    public function test_a_promo_outside_its_date_range_is_not_currently_active(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Es Teh', 'price' => 5000, 'cost_price' => 2000, 'stock_qty' => 20]);

        $expired = Promo::create([
            'store_id' => $store->id,
            'name' => 'Promo Lalu',
            'type' => 'discount',
            'product_id' => $product->id,
            'discount_type' => 'percent',
            'discount_value' => 50,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $this->assertFalse($expired->isCurrentlyActive());
    }
}
