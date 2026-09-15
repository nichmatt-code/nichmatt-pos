<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AccountSwitcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_logging_in_remembers_the_account_for_switching(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login');

        $this->assertSame([$user->id], session('linked_accounts'));
    }

    public function test_adding_another_account_keeps_the_first_linked_and_switches_active_session(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $first = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $second = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        $this->actingAs($first);
        session(['linked_accounts' => [$first->id]]);

        Volt::test('pages.accounts.add')
            ->set('email', $second->email)
            ->set('password', 'password')
            ->call('addAccount')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($second);
        $this->assertEqualsCanonicalizing([$first->id, $second->id], session('linked_accounts'));
    }

    public function test_user_can_switch_to_a_linked_account_without_a_password(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $first = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $second = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $this->actingAs($first);
        session(['linked_accounts' => [$first->id, $second->id]]);

        Volt::test('layout.navigation')
            ->call('switchToAccount', $second->id);

        $this->assertAuthenticatedAs($second);
    }

    public function test_switching_to_an_account_never_linked_in_this_session_is_forbidden(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $first = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $stranger = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $this->actingAs($first);
        session(['linked_accounts' => [$first->id]]);

        Volt::test('layout.navigation')
            ->call('switchToAccount', $stranger->id)
            ->assertStatus(403);

        $this->assertAuthenticatedAs($first);
    }

    public function test_forgetting_a_non_active_linked_account_just_removes_it(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $first = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $second = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $this->actingAs($first);
        session(['linked_accounts' => [$first->id, $second->id]]);

        Volt::test('layout.navigation')
            ->call('forgetLinkedAccount', $second->id);

        $this->assertSame([$first->id], session('linked_accounts'));
        $this->assertAuthenticatedAs($first);
    }

    public function test_forgetting_the_active_account_switches_to_the_next_linked_one(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $first = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $second = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $this->actingAs($second);
        session(['linked_accounts' => [$first->id, $second->id]]);

        Volt::test('layout.navigation')
            ->call('forgetLinkedAccount', $second->id);

        $this->assertAuthenticatedAs($first);
        $this->assertSame([$first->id], session('linked_accounts'));
    }

    public function test_forgetting_the_only_linked_account_logs_out_completely(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $user = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $this->actingAs($user);
        session(['linked_accounts' => [$user->id]]);

        Volt::test('layout.navigation')
            ->call('forgetLinkedAccount', $user->id);

        $this->assertGuest();
    }
}
