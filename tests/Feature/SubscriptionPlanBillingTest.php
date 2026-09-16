<?php

namespace Tests\Feature;

use App\Livewire\Billing\Subscribe;
use App\Models\PromoCode;
use App\Models\Store;
use App\Models\StoreSubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SubscriptionPlanBillingTest extends TestCase
{
    use RefreshDatabase;

    private function signedNotification(StoreSubscriptionPayment $payment, string $status = 'settlement'): array
    {
        $statusCode = '200';
        $grossAmount = number_format($payment->amount, 2, '.', '');
        $signature = hash('sha512', $payment->order_id.$statusCode.$grossAmount.'test-server-key');

        return [
            'order_id' => $payment->order_id,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
            'transaction_status' => $status,
            'payment_type' => 'qris',
            'transaction_id' => 'trx-'.$payment->id,
        ];
    }

    public function test_the_two_launch_plans_are_seeded_by_the_migration(): void
    {
        $weekly = SubscriptionPlan::where('code', 'weekly')->firstOrFail();
        $monthly = SubscriptionPlan::where('code', 'monthly')->firstOrFail();

        $this->assertSame(7, $weekly->duration_days);
        $this->assertSame(35000, $weekly->price);
        $this->assertSame(30, $monthly->duration_days);
        $this->assertSame(100000, $monthly->price);
    }

    public function test_subscribe_page_lists_active_plans_and_defaults_to_the_first(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Subscribe::class)
            ->assertSee('Langganan Mingguan')
            ->assertSee('Langganan Bulanan')
            ->assertSet('selectedPlanId', SubscriptionPlan::where('code', 'weekly')->value('id'));
    }

    public function test_applying_a_fixed_promo_code_reduces_the_final_price(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $monthly = SubscriptionPlan::where('code', 'monthly')->firstOrFail();
        $promo = PromoCode::create(['code' => 'HEMAT10K', 'type' => 'fixed', 'value' => 10000, 'is_active' => true]);

        Livewire::actingAs($owner)
            ->test(Subscribe::class)
            ->call('selectPlan', $monthly->id)
            ->set('promoCodeInput', 'hemat10k')
            ->call('applyPromoCode')
            ->assertHasNoErrors()
            ->assertSet('finalPrice', 90000);

        $this->assertTrue(true);
    }

    public function test_applying_a_percent_promo_code_reduces_the_final_price(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $monthly = SubscriptionPlan::where('code', 'monthly')->firstOrFail();
        PromoCode::create(['code' => 'DISKON20', 'type' => 'percent', 'value' => 20, 'is_active' => true]);

        Livewire::actingAs($owner)
            ->test(Subscribe::class)
            ->call('selectPlan', $monthly->id)
            ->set('promoCodeInput', 'DISKON20')
            ->call('applyPromoCode')
            ->assertSet('finalPrice', 80000);
    }

    public function test_an_invalid_promo_code_shows_an_error_and_does_not_change_the_price(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $monthly = SubscriptionPlan::where('code', 'monthly')->firstOrFail();

        Livewire::actingAs($owner)
            ->test(Subscribe::class)
            ->call('selectPlan', $monthly->id)
            ->set('promoCodeInput', 'TIDAKADA')
            ->call('applyPromoCode')
            ->assertHasErrors('promoCodeInput')
            ->assertSet('finalPrice', 100000);
    }

    public function test_an_expired_promo_code_is_rejected(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        PromoCode::create(['code' => 'KADALUARSA', 'type' => 'fixed', 'value' => 5000, 'is_active' => true, 'expires_at' => now()->subDay()]);

        Livewire::actingAs($owner)
            ->test(Subscribe::class)
            ->set('promoCodeInput', 'KADALUARSA')
            ->call('applyPromoCode')
            ->assertHasErrors('promoCodeInput');
    }

    public function test_a_fully_redeemed_promo_code_is_rejected(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        PromoCode::create(['code' => 'HABIS', 'type' => 'fixed', 'value' => 5000, 'is_active' => true, 'max_redemptions' => 1, 'times_redeemed' => 1]);

        Livewire::actingAs($owner)
            ->test(Subscribe::class)
            ->set('promoCodeInput', 'HABIS')
            ->call('applyPromoCode')
            ->assertHasErrors('promoCodeInput');
    }

    public function test_subscribing_creates_a_payment_snapshotting_the_plan_promo_and_duration(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $weekly = SubscriptionPlan::where('code', 'weekly')->firstOrFail();
        $promo = PromoCode::create(['code' => 'HEMAT5K', 'type' => 'fixed', 'value' => 5000, 'is_active' => true]);

        Livewire::actingAs($owner)
            ->test(Subscribe::class)
            ->call('selectPlan', $weekly->id)
            ->set('promoCodeInput', 'HEMAT5K')
            ->call('applyPromoCode')
            ->call('subscribe');

        $payment = StoreSubscriptionPayment::where('store_id', $store->id)->firstOrFail();
        $this->assertSame($weekly->id, $payment->subscription_plan_id);
        $this->assertSame($promo->id, $payment->promo_code_id);
        $this->assertSame(7, $payment->duration_days);
        $this->assertSame(30000, $payment->amount);
        $this->assertSame('pending', $payment->status);
    }

    public function test_webhook_activates_subscription_for_the_plans_duration(): void
    {
        config(['services.midtrans.server_key' => 'test-server-key']);

        $store = Store::factory()->create(['trial_ends_at' => now()->subDay()]);
        $weekly = SubscriptionPlan::where('code', 'weekly')->firstOrFail();
        $payment = StoreSubscriptionPayment::create([
            'store_id' => $store->id,
            'subscription_plan_id' => $weekly->id,
            'order_id' => 'SUB-WEEKLY-1',
            'amount' => 35000,
            'duration_days' => 7,
            'status' => 'pending',
        ]);

        $this->postJson('/midtrans/notification', $this->signedNotification($payment))->assertOk();

        $store->refresh();
        $this->assertTrue($store->subscriptionActive());
        $this->assertEqualsWithDelta(now()->addDays(7)->timestamp, $store->subscription_ends_at->timestamp, 5);
    }

    public function test_renewing_while_still_active_stacks_the_new_duration_onto_the_remaining_time(): void
    {
        config(['services.midtrans.server_key' => 'test-server-key']);

        $store = Store::factory()->create([
            'trial_ends_at' => now()->subDay(),
            'subscription_status' => 'active',
            'subscription_ends_at' => now()->addDays(30),
        ]);
        $monthly = SubscriptionPlan::where('code', 'monthly')->firstOrFail();
        $payment = StoreSubscriptionPayment::create([
            'store_id' => $store->id,
            'subscription_plan_id' => $monthly->id,
            'order_id' => 'SUB-RENEW-1',
            'amount' => 100000,
            'duration_days' => 30,
            'status' => 'pending',
        ]);

        $this->postJson('/midtrans/notification', $this->signedNotification($payment))->assertOk();

        $store->refresh();
        // Started with 30 days left, renewed for another 30 => ~60 days left.
        $this->assertEqualsWithDelta(60, now()->diffInDays($store->subscription_ends_at), 1);
    }

    public function test_a_duplicate_settlement_notification_does_not_extend_the_subscription_twice(): void
    {
        config(['services.midtrans.server_key' => 'test-server-key']);

        $store = Store::factory()->create(['trial_ends_at' => now()->subDay()]);
        $monthly = SubscriptionPlan::where('code', 'monthly')->firstOrFail();
        $payment = StoreSubscriptionPayment::create([
            'store_id' => $store->id,
            'subscription_plan_id' => $monthly->id,
            'order_id' => 'SUB-DUPLICATE-1',
            'amount' => 100000,
            'duration_days' => 30,
            'status' => 'pending',
        ]);

        $notification = $this->signedNotification($payment);
        $this->postJson('/midtrans/notification', $notification)->assertOk();
        $endsAtAfterFirst = $store->refresh()->subscription_ends_at;

        $this->postJson('/midtrans/notification', $notification)->assertOk();
        $endsAtAfterSecond = $store->refresh()->subscription_ends_at;

        $this->assertTrue($endsAtAfterFirst->equalTo($endsAtAfterSecond));
    }

    public function test_settling_a_payment_with_a_promo_code_increments_its_redemption_count_only_once(): void
    {
        config(['services.midtrans.server_key' => 'test-server-key']);

        $store = Store::factory()->create(['trial_ends_at' => now()->subDay()]);
        $monthly = SubscriptionPlan::where('code', 'monthly')->firstOrFail();
        $promo = PromoCode::create(['code' => 'PROMO1', 'type' => 'fixed', 'value' => 10000, 'is_active' => true]);
        $payment = StoreSubscriptionPayment::create([
            'store_id' => $store->id,
            'subscription_plan_id' => $monthly->id,
            'promo_code_id' => $promo->id,
            'order_id' => 'SUB-PROMO-1',
            'amount' => 90000,
            'duration_days' => 30,
            'status' => 'pending',
        ]);

        $notification = $this->signedNotification($payment);
        $this->postJson('/midtrans/notification', $notification);
        $this->postJson('/midtrans/notification', $notification);

        $this->assertSame(1, $promo->fresh()->times_redeemed);
    }

    public function test_trial_notice_is_hidden_once_a_store_subscribes_during_its_trial(): void
    {
        $store = Store::factory()->create([
            'trial_ends_at' => now()->addDays(20),
            'subscription_status' => 'active',
            'subscription_ends_at' => now()->addDays(7),
        ]);

        $this->assertTrue($store->onTrial());
        $this->assertFalse($store->shouldShowTrialNotice());
    }

    public function test_trial_notice_still_shows_while_genuinely_on_trial_with_no_subscription(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(20)]);

        $this->assertTrue($store->shouldShowTrialNotice());
    }
}
