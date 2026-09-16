<?php

namespace Tests\Feature;

use App\Livewire\Developer\Pricing;
use App\Livewire\Developer\StoreShow;
use App\Models\PromoCode;
use App\Models\Store;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeveloperPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_developer_cannot_access_the_pricing_page(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $this->actingAs($owner)->get('/developer/pricing')->assertForbidden();
    }

    public function test_developer_can_create_a_plan(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $developer = User::factory()->create(['store_id' => $store->id, 'role' => 'owner', 'is_developer' => true]);

        Livewire::actingAs($developer)
            ->test(Pricing::class)
            ->call('createPlan')
            ->set('planCode', 'yearly')
            ->set('planName', 'Langganan Tahunan')
            ->set('planDurationDays', '365')
            ->set('planPrice', '1000000')
            ->call('savePlan')
            ->assertHasNoErrors();

        $plan = SubscriptionPlan::where('code', 'yearly')->firstOrFail();
        $this->assertSame(365, $plan->duration_days);
        $this->assertSame(1000000, $plan->price);
    }

    public function test_developer_can_set_a_promo_price_on_a_plan(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $developer = User::factory()->create(['store_id' => $store->id, 'role' => 'owner', 'is_developer' => true]);
        $plan = SubscriptionPlan::where('code', 'monthly')->firstOrFail();

        Livewire::actingAs($developer)
            ->test(Pricing::class)
            ->call('editPlan', $plan->id)
            ->set('planPromoPrice', '75000')
            ->set('planPromoLabel', 'Promo Peluncuran')
            ->call('savePlan')
            ->assertHasNoErrors();

        $plan->refresh();
        $this->assertTrue($plan->hasActivePromo());
        $this->assertSame(75000, $plan->effectivePrice());
    }

    public function test_editing_a_plan_without_changing_its_code_does_not_trigger_a_unique_error(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $developer = User::factory()->create(['store_id' => $store->id, 'role' => 'owner', 'is_developer' => true]);
        $plan = SubscriptionPlan::where('code', 'monthly')->firstOrFail();

        Livewire::actingAs($developer)
            ->test(Pricing::class)
            ->call('editPlan', $plan->id)
            ->set('planName', 'Langganan Bulanan (Update)')
            ->call('savePlan')
            ->assertHasNoErrors();

        $this->assertSame('Langganan Bulanan (Update)', $plan->fresh()->name);
    }

    public function test_creating_a_plan_with_a_duplicate_code_is_rejected(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $developer = User::factory()->create(['store_id' => $store->id, 'role' => 'owner', 'is_developer' => true]);

        Livewire::actingAs($developer)
            ->test(Pricing::class)
            ->call('createPlan')
            ->set('planCode', 'monthly')
            ->set('planName', 'Duplikat')
            ->set('planDurationDays', '30')
            ->set('planPrice', '50000')
            ->call('savePlan')
            ->assertHasErrors('planCode');
    }

    public function test_developer_can_delete_a_plan(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $developer = User::factory()->create(['store_id' => $store->id, 'role' => 'owner', 'is_developer' => true]);
        $plan = SubscriptionPlan::where('code', 'weekly')->firstOrFail();

        Livewire::actingAs($developer)
            ->test(Pricing::class)
            ->call('deletePlan', $plan->id);

        $this->assertModelMissing($plan);
    }

    public function test_developer_can_create_a_promo_code(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $developer = User::factory()->create(['store_id' => $store->id, 'role' => 'owner', 'is_developer' => true]);

        Livewire::actingAs($developer)
            ->test(Pricing::class)
            ->call('createPromo')
            ->set('promoCode', 'launch20')
            ->set('promoType', 'percent')
            ->set('promoValue', '20')
            ->call('savePromo')
            ->assertHasNoErrors();

        $promo = PromoCode::where('code', 'LAUNCH20')->firstOrFail();
        $this->assertSame('percent', $promo->type);
        $this->assertSame(20, $promo->value);
        $this->assertTrue($promo->isValid());
    }

    public function test_developer_can_deactivate_a_promo_code(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $developer = User::factory()->create(['store_id' => $store->id, 'role' => 'owner', 'is_developer' => true]);
        $promo = PromoCode::create(['code' => 'NONAKTIF', 'type' => 'fixed', 'value' => 5000, 'is_active' => true]);

        Livewire::actingAs($developer)
            ->test(Pricing::class)
            ->call('editPromo', $promo->id)
            ->set('promoIsActive', false)
            ->call('savePromo');

        $this->assertFalse($promo->fresh()->isValid());
    }

    public function test_developer_can_manually_extend_a_stores_subscription_without_payment(): void
    {
        $developerStore = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $developer = User::factory()->create(['store_id' => $developerStore->id, 'role' => 'owner', 'is_developer' => true]);
        $targetStore = Store::factory()->create(['trial_ends_at' => now()->subDay(), 'subscription_status' => 'inactive']);

        Livewire::actingAs($developer)
            ->test(StoreShow::class, ['store' => $targetStore])
            ->set('extendDays', '30')
            ->call('extend')
            ->assertHasNoErrors();

        $targetStore->refresh();
        $this->assertTrue($targetStore->subscriptionActive());
        $this->assertEqualsWithDelta(30, now()->diffInDays($targetStore->subscription_ends_at), 1);
    }

    public function test_manually_extending_an_already_active_store_stacks_onto_the_remaining_time(): void
    {
        $developerStore = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $developer = User::factory()->create(['store_id' => $developerStore->id, 'role' => 'owner', 'is_developer' => true]);
        $targetStore = Store::factory()->create([
            'trial_ends_at' => now()->subDay(),
            'subscription_status' => 'active',
            'subscription_ends_at' => now()->addDays(10),
        ]);

        Livewire::actingAs($developer)
            ->test(StoreShow::class, ['store' => $targetStore])
            ->set('extendDays', '30')
            ->call('extend');

        $targetStore->refresh();
        $this->assertEqualsWithDelta(40, now()->diffInDays($targetStore->subscription_ends_at), 1);
    }

    public function test_developer_can_set_an_exact_expiry_date_for_a_store(): void
    {
        $developerStore = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $developer = User::factory()->create(['store_id' => $developerStore->id, 'role' => 'owner', 'is_developer' => true]);
        $targetStore = Store::factory()->create(['trial_ends_at' => now()->subDay()]);
        $futureDate = now()->addDays(90)->format('Y-m-d');

        Livewire::actingAs($developer)
            ->test(StoreShow::class, ['store' => $targetStore])
            ->set('customExpiryDate', $futureDate)
            ->call('setExpiry')
            ->assertHasNoErrors();

        $targetStore->refresh();
        $this->assertTrue($targetStore->subscriptionActive());
        $this->assertSame($futureDate, $targetStore->subscription_ends_at->format('Y-m-d'));
    }

    public function test_extend_rejects_a_non_positive_number_of_days(): void
    {
        $developerStore = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $developer = User::factory()->create(['store_id' => $developerStore->id, 'role' => 'owner', 'is_developer' => true]);
        $targetStore = Store::factory()->create();

        Livewire::actingAs($developer)
            ->test(StoreShow::class, ['store' => $targetStore])
            ->set('extendDays', '0')
            ->call('extend')
            ->assertHasErrors('extendDays');
    }
}
