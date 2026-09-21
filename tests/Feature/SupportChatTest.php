<?php

namespace Tests\Feature;

use App\Livewire\Developer\SupportInbox;
use App\Livewire\Support\ChatWidget;
use App\Models\Store;
use App\Models\SupportConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class SupportChatTest extends TestCase
{
    use RefreshDatabase;

    private function storeUser(array $attributes = []): User
    {
        $store = Store::factory()->create(['trial_ends_at' => now()->addDays(10)]);

        return User::factory()->create(array_merge(['store_id' => $store->id, 'role' => 'owner'], $attributes));
    }

    private function fakeAi(string $reply = 'Buka menu Produk lalu klik Tambah.'): void
    {
        config(['services.anthropic.key' => 'test-key', 'services.anthropic.model' => 'claude-test']);

        Http::fake([
            'api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => $reply]]]),
        ]);
    }

    public function test_the_help_sticker_is_shown_on_app_pages(): void
    {
        $user = $this->storeUser();

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSeeLivewire(ChatWidget::class);
    }

    public function test_the_ai_answers_a_user_message(): void
    {
        $this->fakeAi();
        $user = $this->storeUser();

        Livewire::actingAs($user)
            ->test(ChatWidget::class)
            ->call('toggle')
            ->set('message', 'Cara tambah produk?')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('message', '')
            ->assertSee('Buka menu Produk lalu klik Tambah.');

        $conversation = SupportConversation::firstOrFail();
        $this->assertSame($user->id, $conversation->user_id);
        $this->assertSame($user->store_id, $conversation->store_id);
        $this->assertTrue($conversation->unread_by_developer);
        $this->assertSame(['user', 'ai'], $conversation->messages->pluck('sender_type')->all());
    }

    public function test_the_request_to_claude_carries_the_guide_the_role_and_the_chat(): void
    {
        $this->fakeAi();
        $kasir = $this->storeUser(['role' => 'kasir', 'permissions' => ['customers']]);

        Livewire::actingAs($kasir)
            ->test(ChatWidget::class)
            ->set('message', 'Bagaimana cara kelola pelanggan?')
            ->call('send');

        Http::assertSent(function (Request $request) {
            $body = $request->data();

            return $request->url() === 'https://api.anthropic.com/v1/messages'
                && $request->hasHeader('x-api-key', 'test-key')
                && $request->hasHeader('anthropic-version', '2023-06-01')
                && $body['model'] === 'claude-test'
                && str_contains($body['system'], 'PANDUAN APLIKASI')
                && str_contains($body['system'], 'Data Pelanggan')
                && $body['messages'] === [['role' => 'user', 'content' => 'Bagaimana cara kelola pelanggan?']];
        });
    }

    public function test_without_an_api_key_the_message_is_forwarded_to_developers(): void
    {
        config(['services.anthropic.key' => null]);
        Http::fake();
        $user = $this->storeUser();

        Livewire::actingAs($user)
            ->test(ChatWidget::class)
            ->call('toggle')
            ->set('message', 'Halo')
            ->call('send')
            ->assertSee('diteruskan ke tim developer')
            ->set('message', 'Ada yang bisa bantu?')
            ->call('send');

        Http::assertNothingSent();
        // The forwarding notice isn't repeated on the follow-up message.
        $this->assertSame(['user', 'system', 'user'], SupportConversation::firstOrFail()->messages->pluck('sender_type')->all());
    }

    public function test_an_ai_failure_keeps_the_message_and_falls_back_to_the_developer_notice(): void
    {
        config(['services.anthropic.key' => 'test-key']);
        Http::fake(['api.anthropic.com/*' => Http::response(['error' => 'boom'], 500)]);
        $user = $this->storeUser();

        Livewire::actingAs($user)
            ->test(ChatWidget::class)
            ->call('toggle')
            ->set('message', 'Halo')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSee('diteruskan ke tim developer');

        $this->assertSame(['user', 'system'], SupportConversation::firstOrFail()->messages->pluck('sender_type')->all());
    }

    public function test_the_ai_stays_quiet_while_a_developer_handles_the_conversation(): void
    {
        $this->fakeAi();
        $user = $this->storeUser();
        SupportConversation::create([
            'user_id' => $user->id, 'store_id' => $user->store_id, 'developer_handling' => true,
        ]);

        Livewire::actingAs($user)
            ->test(ChatWidget::class)
            ->set('message', 'Masih ada masalah')
            ->call('send');

        Http::assertNothingSent();
        $this->assertSame(['user'], SupportConversation::firstOrFail()->messages->pluck('sender_type')->all());
    }

    public function test_message_is_required_and_capped_at_1000_characters(): void
    {
        $this->fakeAi();
        $user = $this->storeUser();

        Livewire::actingAs($user)
            ->test(ChatWidget::class)
            ->set('message', '   ')
            ->call('send')
            ->assertHasErrors(['message' => 'required'])
            ->set('message', str_repeat('a', 1001))
            ->call('send')
            ->assertHasErrors(['message' => 'max']);

        $this->assertSame(0, SupportConversation::count());
    }

    public function test_messages_are_rate_limited_per_user(): void
    {
        $this->fakeAi();
        $user = $this->storeUser();
        RateLimiter::clear('support-chat:'.$user->id);

        $component = Livewire::actingAs($user)->test(ChatWidget::class);

        foreach (range(1, 15) as $i) {
            $component->set('message', "pesan {$i}")->call('send')->assertHasNoErrors();
        }

        $component->set('message', 'pesan ke-16')->call('send')->assertHasErrors('message');
    }

    public function test_a_user_only_sees_their_own_conversation(): void
    {
        $this->fakeAi();
        $alice = $this->storeUser(['name' => 'Alice']);
        $bob = $this->storeUser(['name' => 'Bob']);

        Livewire::actingAs($bob)
            ->test(ChatWidget::class)
            ->set('message', 'Rahasia Bob')
            ->call('send');

        Livewire::actingAs($alice)
            ->test(ChatWidget::class)
            ->call('toggle')
            ->assertDontSee('Rahasia Bob');
    }

    public function test_a_developer_reply_reaches_the_user_and_takes_over_from_the_ai(): void
    {
        $this->fakeAi();
        $user = $this->storeUser();
        $developer = $this->storeUser(['name' => 'Dev Nichmatt', 'is_developer' => true]);

        Livewire::actingAs($user)->test(ChatWidget::class)->set('message', 'Struk tidak keluar')->call('send');
        $conversation = SupportConversation::firstOrFail();

        Livewire::actingAs($developer)
            ->test(SupportInbox::class)
            ->call('select', $conversation->id)
            ->set('reply', 'Coba cek koneksi printer ya')
            ->call('sendReply')
            ->assertHasNoErrors()
            ->assertSee('Coba cek koneksi printer ya');

        $conversation->refresh();
        $this->assertTrue($conversation->developer_handling);
        $this->assertTrue($conversation->unread_by_user);
        $this->assertFalse($conversation->unread_by_developer);

        Livewire::actingAs($user)
            ->test(ChatWidget::class)
            ->assertSet('open', false)
            ->call('toggle')
            ->assertSee('Coba cek koneksi printer ya')
            ->assertSee('Developer');

        $this->assertFalse($conversation->fresh()->unread_by_user);
    }

    public function test_developer_can_hand_the_conversation_back_and_close_it(): void
    {
        $developer = $this->storeUser(['is_developer' => true]);
        $user = $this->storeUser();
        $conversation = SupportConversation::create([
            'user_id' => $user->id, 'store_id' => $user->store_id, 'developer_handling' => true,
        ]);

        Livewire::actingAs($developer)
            ->test(SupportInbox::class)
            ->set('selectedId', $conversation->id)
            ->call('returnToAi');
        $this->assertFalse($conversation->fresh()->developer_handling);

        Livewire::actingAs($developer)
            ->test(SupportInbox::class)
            ->set('selectedId', $conversation->id)
            ->call('close');
        $this->assertSame(SupportConversation::STATUS_CLOSED, $conversation->fresh()->status);

        Livewire::actingAs($developer)
            ->test(SupportInbox::class)
            ->set('selectedId', $conversation->id)
            ->call('reopen');
        $this->assertTrue($conversation->fresh()->isOpen());
    }

    public function test_the_inbox_lists_conversations_from_every_store(): void
    {
        $developer = $this->storeUser(['is_developer' => true]);
        $userA = $this->storeUser(['name' => 'Pengguna Toko A']);
        $userB = $this->storeUser(['name' => 'Pengguna Toko B']);
        foreach ([$userA, $userB] as $user) {
            SupportConversation::create(['user_id' => $user->id, 'store_id' => $user->store_id, 'last_message_at' => now()]);
        }

        Livewire::actingAs($developer)
            ->test(SupportInbox::class)
            ->assertSee('Pengguna Toko A')
            ->assertSee('Pengguna Toko B');
    }

    public function test_a_new_message_after_the_conversation_was_closed_starts_a_new_one(): void
    {
        $this->fakeAi();
        $user = $this->storeUser();
        SupportConversation::create([
            'user_id' => $user->id, 'store_id' => $user->store_id, 'status' => SupportConversation::STATUS_CLOSED,
        ]);

        Livewire::actingAs($user)->test(ChatWidget::class)->set('message', 'Halo lagi')->call('send');

        $this->assertSame(2, SupportConversation::count());
        $this->assertTrue(SupportConversation::orderByDesc('id')->first()->isOpen());
    }

    public function test_only_developers_can_open_the_inbox(): void
    {
        $owner = $this->storeUser();
        $developer = $this->storeUser(['is_developer' => true]);

        $this->actingAs($owner)->get('/developer/support')->assertForbidden();
        $this->actingAs($developer)->get('/developer/support')->assertOk()->assertSeeLivewire(SupportInbox::class);

        Livewire::actingAs($owner)->test(SupportInbox::class)->assertForbidden();
    }

    public function test_a_non_developer_cannot_reply_through_the_inbox_component(): void
    {
        $developer = $this->storeUser(['is_developer' => true]);
        $owner = $this->storeUser();
        $conversation = SupportConversation::create(['user_id' => $owner->id, 'store_id' => $owner->store_id]);

        $component = Livewire::actingAs($developer)
            ->test(SupportInbox::class)
            ->set('selectedId', $conversation->id)
            ->set('reply', 'x');

        // Access revoked while the page is still open.
        $developer->update(['is_developer' => false]);
        $this->app->make('auth')->forgetGuards();

        $component->call('sendReply')->assertForbidden();
        $this->assertSame(0, $conversation->messages()->count());
    }
}
