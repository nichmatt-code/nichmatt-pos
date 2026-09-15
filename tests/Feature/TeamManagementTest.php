<?php

namespace Tests\Feature;

use App\Livewire\Team\Index;
use App\Mail\EmployeeInvitationMail;
use App\Models\EmployeeInvitation;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_invite_an_employee_with_specific_permissions(): void
    {
        Mail::fake();

        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('openInviteModal')
            ->set('inviteEmail', 'karyawan@example.com')
            ->set('inviteRole', 'kasir')
            ->set('invitePermissions', ['products', 'reports'])
            ->call('sendInvite')
            ->assertHasNoErrors();

        $invitation = EmployeeInvitation::where('email', 'karyawan@example.com')->firstOrFail();

        $this->assertSame($store->id, $invitation->store_id);
        $this->assertSame(['products', 'reports'], $invitation->permissions);
        Mail::assertSent(EmployeeInvitationMail::class);
    }

    public function test_non_owner_cannot_send_an_invitation_even_with_employees_permission(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $kasir = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir', 'permissions' => ['employees']]);

        Livewire::actingAs($kasir)
            ->test(Index::class)
            ->set('inviteEmail', 'karyawan@example.com')
            ->set('inviteRole', 'kasir')
            ->call('sendInvite');

        $this->assertDatabaseMissing('employee_invitations', ['email' => 'karyawan@example.com']);
    }

    public function test_invited_employee_can_accept_and_gets_the_assigned_permissions(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        $invitation = EmployeeInvitation::create([
            'store_id' => $store->id,
            'invited_by' => $owner->id,
            'email' => 'karyawan@example.com',
            'role' => 'kasir',
            'permissions' => ['reports'],
            'expires_at' => now()->addDays(7),
        ]);

        $component = Volt::test('pages.invitations.accept', ['invitation' => $invitation])
            ->set('name', 'Karyawan Baru')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('accept');

        $component->assertHasNoErrors();

        $user = User::where('email', 'karyawan@example.com')->firstOrFail();

        $this->assertSame($store->id, $user->store_id);
        $this->assertSame(['reports'], $user->permissions);
        $this->assertNotNull($invitation->fresh()->accepted_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_accepting_an_invitation_whose_email_already_has_an_account_shows_a_friendly_message_instead_of_crashing(): void
    {
        $invitedStore = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $invitedStore->id, 'role' => 'owner']);

        $invitation = EmployeeInvitation::create([
            'store_id' => $invitedStore->id,
            'invited_by' => $owner->id,
            'email' => 'sudah.daftar@example.com',
            'role' => 'kasir',
            'expires_at' => now()->addDays(7),
        ]);

        // The invitee registers their own store with the same email before
        // ever clicking the invite link.
        $otherStore = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        User::factory()->create([
            'store_id' => $otherStore->id,
            'role' => 'owner',
            'email' => 'sudah.daftar@example.com',
        ]);

        $component = Volt::test('pages.invitations.accept', ['invitation' => $invitation])
            ->set('name', 'Coba Gabung')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('accept');

        $component->assertHasNoErrors();
        $this->assertGuest();
        $this->assertNull($invitation->fresh()->accepted_at);
        $this->assertSame(1, User::where('email', 'sudah.daftar@example.com')->count());
    }

    public function test_owner_can_edit_another_employees_role_and_permissions(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $kasir = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir', 'permissions' => []]);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('openEditModal', $kasir->id)
            ->set('editPermissions', ['products', 'categories'])
            ->call('saveEdit')
            ->assertHasNoErrors();

        $this->assertSame(['products', 'categories'], $kasir->fresh()->permissions);
    }

    public function test_owner_cannot_edit_their_own_role(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('openEditModal', $owner->id)
            ->set('editRole', 'kasir')
            ->call('saveEdit')
            ->assertHasErrors('editRole');

        $this->assertSame('owner', $owner->fresh()->role);
    }

    public function test_a_second_owner_can_be_demoted_while_one_owner_remains(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $secondOwner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('openEditModal', $secondOwner->id)
            ->set('editRole', 'kasir')
            ->call('saveEdit')
            ->assertHasNoErrors();

        $this->assertSame('kasir', $secondOwner->fresh()->role);
    }

    public function test_the_only_remaining_owner_cannot_be_deactivated(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $secondOwner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('toggleActive', $secondOwner->id);

        $this->assertFalse($secondOwner->fresh()->is_active);

        Livewire::actingAs($secondOwner->fresh())
            ->test(Index::class)
            ->call('toggleActive', $owner->id);

        $this->assertTrue($owner->fresh()->is_active);
    }

    public function test_deactivating_an_employee_keeps_their_transaction_history(): void
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $owner = User::factory()->create(['store_id' => $store->id, 'role' => 'owner']);
        $kasir = User::factory()->create(['store_id' => $store->id, 'role' => 'kasir']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('toggleActive', $kasir->id);

        $this->assertFalse($kasir->fresh()->is_active);
        $this->assertDatabaseHas('users', ['id' => $kasir->id]);
    }
}
