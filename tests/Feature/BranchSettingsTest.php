<?php

namespace Tests\Feature;

use App\Livewire\Branch\Settings;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BranchSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_branch_information(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10), 'name' => 'Toko Lama']);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Settings::class)
            ->set('name', 'Toko Baru')
            ->set('address', 'Jl. Baru No. 1')
            ->set('phone', '08123456789')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Toko Baru', $store->fresh()->name);
        $this->assertSame('Jl. Baru No. 1', $store->fresh()->address);
    }

    public function test_owner_can_customize_the_receipt_width_and_footer_text(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Settings::class)
            ->set('receiptFormat', 'thermal')
            ->set('receiptWidth', '58mm')
            ->set('receiptFooterText', 'Sampai jumpa lagi!')
            ->call('saveReceiptFormat')
            ->assertHasNoErrors();

        $fresh = $store->fresh();
        $this->assertSame('58mm', $fresh->receipt_width);
        $this->assertSame('Sampai jumpa lagi!', $fresh->receipt_footer_text);
        $this->assertSame('Sampai jumpa lagi!', $fresh->receiptFooterText());
    }

    public function test_receipt_footer_falls_back_to_the_default_when_not_customized(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);

        $this->assertSame(Store::DEFAULT_RECEIPT_FOOTER, $store->receiptFooterText());
    }

    public function test_owner_can_enable_online_payments_with_a_server_key(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Settings::class)
            ->set('midtransPaymentEnabled', true)
            ->set('midtransServerKey', 'SB-Mid-server-abc123')
            ->set('midtransClientKey', 'SB-Mid-client-abc123')
            ->call('saveMidtransSettings')
            ->assertHasNoErrors()
            ->assertSet('midtransServerKey', '')
            ->assertSet('hasMidtransServerKey', true);

        $fresh = $store->fresh();
        $this->assertTrue($fresh->midtrans_payment_enabled);
        $this->assertSame('SB-Mid-server-abc123', $fresh->midtrans_server_key);
        $this->assertSame('SB-Mid-client-abc123', $fresh->midtrans_client_key);
    }

    public function test_enabling_online_payments_without_ever_saving_a_server_key_fails(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Settings::class)
            ->set('midtransPaymentEnabled', true)
            ->set('midtransServerKey', '')
            ->call('saveMidtransSettings')
            ->assertHasErrors(['midtransServerKey']);

        $this->assertFalse($store->fresh()->midtrans_payment_enabled);
    }

    public function test_saving_settings_without_a_new_server_key_keeps_the_previously_saved_one(): void
    {
        $store = Store::factory()->create([
            'trial_ends_at' => now()->addDays(10),
            'midtrans_payment_enabled' => true,
            'midtrans_server_key' => 'SB-Mid-server-original',
        ]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Settings::class)
            ->set('midtransIsProduction', true)
            ->call('saveMidtransSettings')
            ->assertHasNoErrors();

        $this->assertSame('SB-Mid-server-original', $store->fresh()->midtrans_server_key);
        $this->assertTrue($store->fresh()->midtrans_is_production);
    }

    public function test_kasir_without_permission_cannot_reach_branch_settings_page(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $kasir = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        $this->actingAs($kasir)->get('/branch')->assertForbidden();
    }

    public function test_kasir_with_store_settings_permission_can_reach_branch_settings_page(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $kasir = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir', 'permissions' => ['store-settings']]);

        $this->actingAs($kasir)->get('/branch')->assertOk();
    }

    public function test_page_shows_active_subscription_expiry_and_a_renew_link(): void
    {
        $store = Store::factory()->create([
            'trial_ends_at' => now()->subDay(),
            'subscription_status' => 'active',
            'subscription_ends_at' => now()->addDays(15),
        ]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Settings::class)
            ->assertSee('Aktif')
            ->assertSee($store->subscription_ends_at->translatedFormat('d F Y'))
            ->assertSeeHtml(route('billing.subscribe'));
    }

    public function test_page_shows_trial_expiry_when_not_yet_subscribed(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(12)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Settings::class)
            ->assertSee('Trial')
            ->assertSee($store->trial_ends_at->translatedFormat('d F Y'));
    }

    public function test_page_shows_expired_status_once_trial_and_subscription_have_lapsed(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->subDays(5)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Settings::class)
            ->assertSee('Kedaluwarsa')
            ->assertSee('Masa aktif sudah habis');
    }

    public function test_owner_can_upload_a_logo_that_appears_on_receipts(): void
    {
        Storage::fake('public');

        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Settings::class)
            ->set('logo', UploadedFile::fake()->image('logo.jpg'))
            ->call('saveLogo')
            ->assertHasNoErrors();

        $store->refresh();
        $this->assertNotNull($store->logo_path);
        Storage::disk('public')->assertExists($store->logo_path);
        $this->assertNotNull($store->logoUrl());
    }

    public function test_removing_the_logo_clears_it_from_storage(): void
    {
        Storage::fake('public');

        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $path = UploadedFile::fake()->image('logo.jpg')->store('logos', 'public');
        $store->update(['logo_path' => $path]);

        Livewire::actingAs($owner)
            ->test(Settings::class)
            ->call('removeLogo')
            ->call('saveLogo')
            ->assertHasNoErrors();

        Storage::disk('public')->assertMissing($path);
        $this->assertNull($store->fresh()->logo_path);
    }

    public function test_owner_can_switch_the_app_logo_to_the_store_logo(): void
    {
        Storage::fake('public');

        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Settings::class)
            ->set('logo', UploadedFile::fake()->image('logo.jpg'))
            ->set('logoSource', 'store')
            ->call('saveLogo')
            ->assertHasNoErrors();

        $store->refresh();
        $this->assertSame('store', $store->logo_source);
        $this->assertSame($store->logoUrl(), $store->appLogoUrl());
    }

    public function test_app_logo_falls_back_to_the_pos_logo_when_store_source_has_no_upload(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10), 'logo_source' => 'store', 'logo_path' => null]);

        $this->assertStringContainsString('images/logo.png', $store->appLogoUrl());
    }
}
