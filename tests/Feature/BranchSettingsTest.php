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
}
