<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-password-form')
            ->set('current_password', 'password')
            ->set('password', 'new-password')
            ->set('password_confirmation', 'new-password')
            ->call('updatePassword');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-password-form')
            ->set('current_password', 'wrong-password')
            ->set('password', 'new-password')
            ->set('password_confirmation', 'new-password')
            ->call('updatePassword');

        $component
            ->assertHasErrors(['current_password'])
            ->assertNoRedirect();
    }

    public function test_a_google_only_account_can_set_a_password_without_a_current_one(): void
    {
        $user = User::factory()->create(['password_set_by_user' => false]);

        $this->actingAs($user);

        $component = Volt::test('profile.update-password-form')
            ->assertSet('needsCurrentPassword', false)
            ->set('password', 'new-password')
            ->set('password_confirmation', 'new-password')
            ->call('updatePassword');

        $component->assertHasNoErrors()->assertNoRedirect();

        $fresh = $user->refresh();
        $this->assertTrue(Hash::check('new-password', $fresh->password));
        $this->assertTrue($fresh->password_set_by_user);
    }

    public function test_once_a_password_is_set_the_form_requires_the_current_password_again(): void
    {
        $user = User::factory()->create(['password_set_by_user' => true]);

        $this->actingAs($user);

        Volt::test('profile.update-password-form')->assertSet('needsCurrentPassword', true);
    }
}
