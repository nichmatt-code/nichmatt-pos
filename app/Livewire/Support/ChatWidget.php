<?php

namespace App\Livewire\Support;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Services\SupportAssistant;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

/**
 * Floating help-chat "sticker" shown on every logged-in page. The user
 * only ever sees their OWN conversation; the AI answers automatically
 * unless a developer has taken the conversation over.
 */
class ChatWidget extends Component
{
    /** Max messages a user can send per minute. */
    private const RATE_LIMIT = 15;

    public bool $open = false;

    public string $message = '';

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
        abort_unless($user, 403);

        $this->message = trim($this->message);

        $this->validate([
            'message' => ['required', 'string', 'max:1000'],
        ], [
            'message.required' => 'Tulis pesan dulu.',
            'message.max' => 'Pesan maksimal 1000 karakter.',
        ]);

        $key = 'support-chat:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT)) {
            $this->addError('message', 'Terlalu banyak pesan, coba lagi sebentar.');

            return;
        }

        RateLimiter::hit($key, 60);

        $conversation = $this->conversation();

        if (! $conversation || ! $conversation->isOpen()) {
            $conversation = SupportConversation::create([
                'user_id' => $user->id,
                'store_id' => $user->store_id,
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

    private function conversation(): ?SupportConversation
    {
        return SupportConversation::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('id')
            ->first();
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
        ]);
    }
}
