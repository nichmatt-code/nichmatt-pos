<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\StoreSubscriptionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_access_dashboard_during_trial(): void
    {
        $store = Store::factory()->create([
            'trial_ends_at' => now()->addDays(10),
            'subscription_status' => 'trial',
        ]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_owner_is_redirected_to_billing_when_trial_has_expired(): void
    {
        $store = Store::factory()->create([
            'trial_ends_at' => now()->subDay(),
            'subscription_status' => 'trial',
        ]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('billing.subscribe'));
    }

    public function test_billing_page_remains_accessible_after_trial_expires(): void
    {
        $store = Store::factory()->create([
            'trial_ends_at' => now()->subDay(),
            'subscription_status' => 'trial',
        ]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $this->actingAs($user)->get('/billing/subscribe')->assertOk();
    }

    public function test_owner_can_access_dashboard_with_active_subscription(): void
    {
        $store = Store::factory()->create([
            'trial_ends_at' => now()->subDay(),
            'subscription_status' => 'active',
            'subscription_ends_at' => now()->addMonth(),
        ]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_midtrans_notification_activates_subscription(): void
    {
        config(['services.midtrans.server_key' => 'test-server-key']);

        $store = Store::factory()->create([
            'trial_ends_at' => now()->subDay(),
            'subscription_status' => 'trial',
        ]);
        $payment = StoreSubscriptionPayment::create([
            'store_id' => $store->id,
            'order_id' => 'SUB-1-TEST',
            'amount' => Store::SUBSCRIPTION_MONTHLY_PRICE,
            'status' => 'pending',
        ]);

        $statusCode = '200';
        $grossAmount = number_format($payment->amount, 2, '.', '');
        $signature = hash('sha512', $payment->order_id.$statusCode.$grossAmount.'test-server-key');

        $response = $this->postJson('/midtrans/notification', [
            'order_id' => $payment->order_id,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
            'transaction_id' => 'trx-123',
        ]);

        $response->assertOk();

        $store->refresh();
        $payment->refresh();

        $this->assertSame('active', $store->subscription_status);
        $this->assertTrue($store->subscriptionActive());
        $this->assertSame('settlement', $payment->status);
    }

    public function test_midtrans_notification_rejects_invalid_signature(): void
    {
        config(['services.midtrans.server_key' => 'test-server-key']);

        $store = Store::factory()->create(['trial_ends_at' => now()->subDay()]);
        $payment = StoreSubscriptionPayment::create([
            'store_id' => $store->id,
            'order_id' => 'SUB-2-TEST',
            'amount' => Store::SUBSCRIPTION_MONTHLY_PRICE,
            'status' => 'pending',
        ]);

        $response = $this->postJson('/midtrans/notification', [
            'order_id' => $payment->order_id,
            'status_code' => '200',
            'gross_amount' => number_format($payment->amount, 2, '.', ''),
            'signature_key' => 'not-a-valid-signature',
            'transaction_status' => 'settlement',
        ]);

        $response->assertForbidden();

        $this->assertSame('pending', $payment->fresh()->status);
    }
}
