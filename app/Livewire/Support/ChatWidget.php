<?php

namespace App\Livewire\Support;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Services\SupportAssistant;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Floating help-chat "sticker". Logged-in users see their OWN conversation
 * on every app page; logged-out landing-page visitors get a guest
 * conversation tied to their browser session. The AI answers
 * automatically unless a developer has taken the conversation over.
 */
class ChatWidget extends Component
{
    private const SESSION_KEY = 'support_guest_token';

    /** Max messages per minute: logged-in user / guest session. */
    private const USER_LIMIT = 15;

    private const GUEST_LIMIT = 8;

    /**
     * Max guest messages per hour from one IP - the guest token is just a
     * session cookie a bot could discard, so the IP is what actually caps
     * how much AI usage an anonymous visitor can cause.
     */
    private const GUEST_IP_HOURLY_LIMIT = 30;

    public bool $open = false;

    public string $message = '';

    /** Optional name a guest can leave so the developer knows who they are. */
    public string $guestName = '';

    public int $shownCount = 0;

    public function toggle(): void
    {
        $this->open = ! $this->open;

        if ($this->open) {
            $this->markRead();
            $this->dispatch('support-scroll');
        }
    }

    public function refresh(): void
    {
        $this->markRead();

        $count = $this->conversation()?->messages()->count() ?? 0;

        if ($count !== $this->shownCount) {
            $this->shownCount = $count;
            $this->dispatch('support-scroll');
        }
    }

    public function send(SupportAssistant $assistant): void
    {
        $user = Auth::user();

        $this->message = trim($this->message);
        $this->guestName = trim($this->guestName);

        $this->validate([
            'message' => ['required', 'string', 'max:1000'],
            'guestName' => ['nullable', 'string', 'max:60'],
        ], [
            'message.required' => 'Tulis pesan dulu.',
            'message.max' => 'Pesan maksimal 1000 karakter.',
            'guestName.max' => 'Nama maksimal 60 karakter.',
        ]);

        if (! $this->withinRateLimits($user !== null)) {
            $this->addError('message', 'Terlalu banyak pesan, coba lagi sebentar.');

            return;
        }

        $conversation = $this->conversation();

        if (! $conversation || ! $conversation->isOpen()) {
            $conversation = SupportConversation::create([
                'user_id' => $user?->id,
                'store_id' => $user?->store_id,
                'guest_token' => $user ? null : $this->guestToken(create: true),
                'guest_name' => $user || $this->guestName === '' ? null : $this->guestName,
                'status' => SupportConversation::STATUS_OPEN,
            ]);
        }

        $conversation->messages()->create([
            'sender_type' => SupportMessage::SENDER_USER,
            'body' => $this->message,
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'unread_by_developer' => true,
        ]);

        $this->reset('message');

        if (! $conversation->developer_handling) {
            $assistant->replyTo($conversation);
        }

        $this->shownCount = $conversation->messages()->count();
        $this->dispatch('support-scroll');
    }

    private function withinRateLimits(bool $loggedIn): bool
    {
        $limits = $loggedIn
            ? ['support-chat:user:'.Auth::id() => [self::USER_LIMIT, 60]]
            : [
                'support-chat:guest:'.$this->guestToken(create: true) => [self::GUEST_LIMIT, 60],
                'support-chat:ip:'.request()->ip() => [self::GUEST_IP_HOURLY_LIMIT, 3600],
            ];

        foreach ($limits as $key => [$max, $seconds]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return false;
            }
        }

        foreach ($limits as $key => [$max, $seconds]) {
            RateLimiter::hit($key, $seconds);
        }

        return true;
    }

    private function guestToken(bool $create = false): ?string
    {
        $token = session(self::SESSION_KEY);

        if (! $token && $create) {
            $token = Str::random(40);
            session([self::SESSION_KEY => $token]);
        }

        return $token;
    }

    private function conversation(): ?SupportConversation
    {
        if (Auth::check()) {
            return SupportConversation::query()
                ->where('user_id', Auth::id())
                ->orderByDesc('id')
                ->first();
        }

        $token = $this->guestToken();

        return $token
            ? SupportConversation::query()->where('guest_token', $token)->orderByDesc('id')->first()
            : null;
    }

    private function markRead(): void
    {
        $this->conversation()?->update(['unread_by_user' => false]);
    }

    public function render(): View
    {
        $conversation = $this->conversation();

        return view('livewire.support.chat-widget', [
            'conversation' => $conversation,
            'messages' => $this->open && $conversation ? $conversation->messages()->get() : collect(),
            'hasUnread' => ! $this->open && (bool) $conversation?->unread_by_user,
            'askForName' => ! Auth::check() && ! $conversation,
        ]);
    }
}
