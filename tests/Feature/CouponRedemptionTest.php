<?php

namespace Tests\Feature;

use App\Livewire\Pos\Terminal;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CouponRedemptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_apply_a_fixed_discount_coupon_at_checkout(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Nasi Goreng', 'price' => 20000, 'cost_price' => 10000, 'stock_qty' => 10]);
        Coupon::create(['store_id' => $store->id, 'code' => 'HEMAT5', 'name' => 'Hemat 5rb', 'discount_type' => 'fixed', 'discount_value' => 5000, 'is_active' => true]);

        $component = Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->set('couponCodeInput', 'hemat5')
            ->call('applyCoupon')
            ->assertHasNoErrors();

        $this->assertSame(5000, $component->get('couponDiscountAmount'));

        $component->set('paymentMethod', 'qris')->call('checkout')->assertHasNoErrors();

        $transaction = Transaction::first();
        $this->assertSame(15000, $transaction->total);
        $this->assertSame(5000, $transaction->coupon_discount_amount);
        $this->assertNotNull($transaction->coupon_id);
    }

    public function test_an_unknown_coupon_code_shows_an_error(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->set('couponCodeInput', 'TIDAKADA')
            ->call('applyCoupon')
            ->assertHasErrors(['couponCodeInput']);
    }

    public function test_a_gift_coupon_adds_a_free_line_that_still_deducts_stock(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Nasi Goreng', 'price' => 20000, 'cost_price' => 10000, 'stock_qty' => 10]);
        $gift = Product::create(['store_id' => $store->id, 'name' => 'Es Teh', 'price' => 5000, 'cost_price' => 2000, 'stock_qty' => 20]);
        Coupon::create(['store_id' => $store->id, 'code' => 'FREETEA', 'name' => 'Gratis Es Teh', 'gift_product_id' => $gift->id, 'gift_qty' => 1, 'is_active' => true]);

        $component = Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('addToCart', $product->id)
            ->set('couponCodeInput', 'FREETEA')
            ->call('applyCoupon');

        $cart = $component->get('cart');
        $this->assertArrayHasKey('coupon_gift', $cart);
        $this->assertSame(0, $cart['coupon_gift']['price']);

        $component->set('paymentMethod', 'qris')->call('checkout')->assertHasNoErrors();

        $this->assertSame(20000, Transaction::first()->total); // gift is free
        $this->assertSame(19, $gift->fresh()->stock_qty);
    }

    public function test_removing_a_coupon_clears_its_discount_and_gift(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $gift = Product::create(['store_id' => $store->id, 'name' => 'Es Teh', 'price' => 5000, 'cost_price' => 2000, 'stock_qty' => 20]);
        Coupon::create(['store_id' => $store->id, 'code' => 'FREETEA', 'name' => 'Gratis Es Teh', 'gift_product_id' => $gift->id, 'gift_qty' => 1, 'is_active' => true]);

        $component = Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->set('couponCodeInput', 'FREETEA')
            ->call('applyCoupon')
            ->call('removeCoupon');

        $this->assertNull($component->get('appliedCouponId'));
        $this->assertArrayNotHasKey('coupon_gift', $component->get('cart'));
    }

    public function test_an_age_based_coupon_requires_a_customer_with_a_birthdate(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        Coupon::create([
            'store_id' => $store->id,
            'code' => 'ULTAH',
            'name' => 'Promo Ulang Tahun',
            'discount_type' => 'percent',
            'discount_value' => 0,
            'is_age_based' => true,
            'age_multiplier' => 1,
            'is_active' => true,
        ]);

        Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->set('couponCodeInput', 'ULTAH')
            ->call('applyCoupon')
            ->assertHasErrors(['couponCodeInput']);
    }

    public function test_an_age_based_coupon_computes_the_discount_from_the_selected_customers_age(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);
        $product = Product::create(['store_id' => $store->id, 'name' => 'Nasi Goreng', 'price' => 100000, 'cost_price' => 50000, 'stock_qty' => 10]);
        $customer = Customer::create(['store_id' => $store->id, 'name' => 'Budi', 'phone' => '0812', 'birthdate' => now()->subYears(25)->format('Y-m-d')]);
        Coupon::create([
            'store_id' => $store->id,
            'code' => 'ULTAH',
            'name' => 'Promo Ulang Tahun',
            'discount_type' => 'percent',
            'discount_value' => 0,
            'is_age_based' => true,
            'age_multiplier' => 1,
            'is_active' => true,
        ]);

        $component = Livewire::actingAs($cashier)
            ->test(Terminal::class)
            ->call('selectCustomer', $customer->id)
            ->call('addToCart', $product->id)
            ->set('couponCodeInput', 'ULTAH')
            ->call('applyCoupon')
            ->assertHasNoErrors();

        // 25% off a 100.000 subtotal.
        $this->assertSame(25000, $component->get('couponDiscountAmount'));
    }
}
