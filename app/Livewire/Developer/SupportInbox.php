<?php

namespace App\Livewire\Developer;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Developer-side inbox for the in-app help chat: every tenant's
 * conversations, newest activity first. A developer reply takes the
 * conversation over from the AI until it's handed back.
 */
class SupportInbox extends Component
{
    public ?int $selectedId = null;

    public string $reply = '';

    /** open | closed | all */
    public string $filter = 'open';

    public function mount(): void
    {
        $this->authorizeDeveloper();
    }

    public function select(int $conversationId): void
    {
        $this->authorizeDeveloper();

        $conversation = SupportConversation::findOrFail($conversationId);

        $this->selectedId = $conversation->id;
        $this->reply = '';
        $conversation->update(['unread_by_developer' => false]);
    }

    public function sendReply(): void
    {
        $this->authorizeDeveloper();

        $this->reply = trim($this->reply);

        $this->validate([
            'reply' => ['required', 'string', 'max:2000'],
        ], [
            'reply.required' => 'Tulis balasan dulu.',
        ]);

        $conversation = SupportConversation::findOrFail($this->selectedId);

        $conversation->messages()->create([
            'sender_type' => SupportMessage::SENDER_DEVELOPER,
            'sender_id' => Auth::id(),
            'body' => $this->reply,
        ]);

        $conversation->update([
            'status' => SupportConversation::STATUS_OPEN,
            'developer_handling' => true,
            'unread_by_user' => true,
            'unread_by_developer' => false,
            'last_message_at' => now(),
        ]);

        $this->reset('reply');
    }

    /** Let the AI answer this user's next messages again. */
    public function returnToAi(): void
    {
        $this->authorizeDeveloper();

        SupportConversation::findOrFail($this->selectedId)->update(['developer_handling' => false]);
    }

    public function close(): void
    {
        $this->authorizeDeveloper();

        SupportConversation::findOrFail($this->selectedId)->update([
            'status' => SupportConversation::STATUS_CLOSED,
            'developer_handling' => false,
        ]);
    }

    public function reopen(): void
    {
        $this->authorizeDeveloper();

        SupportConversation::findOrFail($this->selectedId)->update(['status' => SupportConversation::STATUS_OPEN]);
    }

    private function authorizeDeveloper(): void
    {
        abort_unless(Auth::user()?->isDeveloper(), 403);
    }

    public function render(): View
    {
        $this->authorizeDeveloper();

        $conversations = SupportConversation::query()
            ->with(['user', 'store'])
            ->withCount('messages')
            ->when($this->filter !== 'all', fn ($query) => $query->where('status', $this->filter))
            ->orderByDesc('last_message_at')
            ->limit(100)
            ->get();

        $selected = $this->selectedId
            ? SupportConversation::with(['user', 'store', 'messages.sender'])->find($this->selectedId)
            : null;

        // A message that arrives while this thread is open counts as seen.
        if ($selected?->unread_by_developer) {
            $selected->update(['unread_by_developer' => false]);
        }

        return view('livewire.developer.support-inbox', [
            'conversations' => $conversations,
            'selected' => $selected,
            'unreadTotal' => SupportConversation::where('unread_by_developer', true)->count(),
        ]);
    }
}
