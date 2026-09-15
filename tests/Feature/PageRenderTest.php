<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_every_owner_page(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $this->actingAs($owner);

        foreach (['dashboard', 'pos', 'products', 'categories', 'reports/sales', 'billing/subscribe', 'profile', 'branch', 'team', 'preparation'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_kasir_is_redirected_from_dashboard_to_pos(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $kasir = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        $this->actingAs($kasir);

        $this->get('dashboard')->assertRedirect(route('pos'));
        $this->get('pos')->assertOk();
        $this->get('profile')->assertOk();
    }
}
