<?php

namespace Tests\Feature;

use App\Livewire\Coupons\Index;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_a_percent_discount_coupon(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('createCoupon')
            ->set('name', 'Diskon Akhir Pekan')
            ->set('code', 'WEEKEND10')
            ->set('includeDiscount', true)
            ->set('discountType', 'percent')
            ->set('discountValue', '10')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('coupons', 1);
        $coupon = Coupon::first();
        $this->assertSame('WEEKEND10', $coupon->code);
        $this->assertSame($owner->id, $coupon->created_by);
        $this->assertSame(1000, $coupon->discountAmountFor(10000));
    }

    public function test_owner_can_create_an_age_based_coupon_with_no_fixed_value(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('createCoupon')
            ->set('name', 'Promo Ulang Tahun')
            ->set('code', 'ULTAH')
            ->set('includeDiscount', true)
            ->set('discountType', 'percent')
            ->set('discountValue', '')
            ->set('isAgeBased', true)
            ->set('ageMultiplier', '1')
            ->call('save')
            ->assertHasNoErrors();

        $coupon = Coupon::first();
        $this->assertTrue($coupon->is_age_based);
        $this->assertSame(0, $coupon->discount_value);
        // 25% off for a 25-year-old, on a 100.000 subtotal.
        $this->assertSame(25000, $coupon->discountAmountFor(100000, 25));
    }

    public function test_a_coupon_needs_at_least_a_discount_or_a_gift(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('createCoupon')
            ->set('name', 'Kupon Kosong')
            ->set('code', 'KOSONG')
            ->call('save')
            ->assertHasErrors(['includeDiscount']);

        $this->assertDatabaseCount('coupons', 0);
    }

    public function test_owner_can_create_a_gift_only_coupon(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Es Teh', 'price' => 5000, 'cost_price' => 2000, 'stock_qty' => 20]);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('createCoupon')
            ->set('name', 'Gratis Es Teh')
            ->set('code', 'FREETEA')
            ->set('includeGift', true)
            ->set('giftProductId', $product->id)
            ->set('giftQty', '1')
            ->call('save')
            ->assertHasNoErrors();

        $coupon = Coupon::first();
        $this->assertFalse($coupon->hasDiscount());
        $this->assertTrue($coupon->hasGift());
        $this->assertSame($product->id, $coupon->gift_product_id);
    }

    public function test_coupon_codes_are_unique_per_store(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        Coupon::create(['store_id' => $store->id, 'code' => 'DUPLICATE', 'name' => 'A', 'discount_type' => 'percent', 'discount_value' => 5, 'is_active' => true]);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('createCoupon')
            ->set('name', 'B')
            ->set('code', 'DUPLICATE')
            ->set('includeDiscount', true)
            ->set('discountType', 'percent')
            ->set('discountValue', '5')
            ->call('save')
            ->assertHasErrors(['code']);
    }
}
