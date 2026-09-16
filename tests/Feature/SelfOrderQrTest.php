<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelfOrderQrTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_the_self_order_qr_page(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $this->actingAs($owner)
            ->get(route('self-order.qr'))
            ->assertOk()
            ->assertSee($store->name)
            ->assertSee($store->selfOrderUrl())
            ->assertSee('<svg', false);
    }

    public function test_guest_cannot_view_the_self_order_qr_page(): void
    {
        $this->get(route('self-order.qr'))->assertRedirect(route('login'));
    }

    public function test_owner_can_download_the_self_order_qr_as_png(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $response = $this->actingAs($owner)->get(route('self-order.qr.download'));

        $response->assertOk();
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }
}
