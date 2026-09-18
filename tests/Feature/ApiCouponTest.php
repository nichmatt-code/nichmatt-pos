<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCouponTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_percent_coupon_is_checked_correctly(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        Coupon::factory()->create([
            'store_id' => $store->id,
            'code' => 'DISKON10',
            'discount_type' => 'percent',
            'discount_value' => 10,
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/coupons/check', ['code' => 'diskon10', 'subtotal' => 100000])
            ->assertOk()
            ->assertJsonPath('data.code', 'DISKON10')
            ->assertJsonPath('data.discount_amount', 10000)
            ->assertJsonPath('data.has_gift', false);
    }

    public function test_an_unknown_coupon_code_is_rejected(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/coupons/check', ['code' => 'TIDAKADA', 'subtotal' => 50000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_an_expired_coupon_is_rejected(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        Coupon::factory()->create([
            'store_id' => $store->id,
            'code' => 'KADALUARSA',
            'ends_at' => now()->subDay(),
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/coupons/check', ['code' => 'KADALUARSA', 'subtotal' => 50000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_an_age_based_coupon_is_rejected_with_a_clear_message(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        Coupon::factory()->create([
            'store_id' => $store->id,
            'code' => 'ULTAH',
            'is_age_based' => true,
            'age_multiplier' => 1000,
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/coupons/check', ['code' => 'ULTAH', 'subtotal' => 50000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_a_coupon_from_another_store_is_not_visible(): void
    {
        $storeA = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $storeB = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $userA = User::factory()->create(['store_id' => $storeA->id, 'role' => 'kasir']);
        Coupon::factory()->create(['store_id' => $storeB->id, 'code' => 'PUNYATOKOB']);
        $token = $userA->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/coupons/check', ['code' => 'PUNYATOKOB', 'subtotal' => 50000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_checkout_applies_a_valid_coupon_to_the_total(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Nasi Goreng',
            'price' => 20000,
            'cost_price' => 12000,
            'stock_qty' => 10,
        ]);
        Coupon::factory()->create([
            'store_id' => $store->id,
            'code' => 'DISKON10',
            'discount_type' => 'percent',
            'discount_value' => 10,
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/transactions', [
                'items' => [['product_id' => $product->id, 'qty' => 1]],
                'coupon_code' => 'diskon10',
                'payment_method' => 'cash',
                'paid_amount' => 18000,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.subtotal', 20000)
            ->assertJsonPath('data.total', 18000);
    }
}
