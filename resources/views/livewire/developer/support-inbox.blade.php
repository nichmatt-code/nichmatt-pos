<div wire:poll.10s>
@unless ($aiConfigured)
    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300">
        {{ __('AI belum aktif: ANTHROPIC_API_KEY belum diisi di server. Sampai diisi, pengguna hanya mendapat potongan panduan otomatis atau pesan "diteruskan ke developer" - balas manual dari sini.') }}
    </div>
@endunless
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <!-- Conversation list -->
    <div class="lg:col-span-1">
        <div class="mb-3 flex items-center justify-between">
            <div class="flex gap-1.5">
                @foreach (['open' => 'Terbuka', 'closed' => 'Ditutup', 'all' => 'Semua'] as $value => $label)
                    <button type="button" wire:click="$set('filter', '{{ $value }}')"
                        class="rounded-full border px-3 py-1.5 text-xs font-semibold transition {{ $filter === $value ? 'border-slate-900 bg-slate-900 text-white dark:border-slate-100 dark:bg-slate-100 dark:text-slate-900' : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-400' }}">
                        {{ __($label) }}
                    </button>
                @endforeach
            </div>
            @if ($unreadTotal > 0)
                <span class="rounded-full bg-rose-500 px-2 py-0.5 text-xs font-semibold text-white">{{ $unreadTotal }} {{ __('baru') }}</span>
            @endif
        </div>

        <div class="space-y-2">
            @forelse ($conversations as $conversation)
                <button type="button" wire:key="conv-{{ $conversation->id }}" wire:click="select({{ $conversation->id }})"
                    class="w-full rounded-xl border p-3 text-left transition {{ $selectedId === $conversation->id ? 'border-brand-300 bg-brand-50 dark:border-brand-700 dark:bg-brand-500/10' : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-700 dark:bg-slate-800/60' }}">
                    <div class="flex items-center justify-between gap-2">
                        <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $conversation->displayName() }}</p>
                        @if ($conversation->unread_by_developer)
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-rose-500"></span>
                        @endif
                    </div>
                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $conversation->isGuest() ? __('Pengunjung landing page') : ($conversation->store?->name ?? '-') }}</p>
                    <div class="mt-1 flex items-center gap-2 text-[11px] text-slate-400 dark:text-slate-500">
                        <span>{{ $conversation->messages_count }} {{ __('pesan') }}</span>
                        <span>&middot;</span>
                        <span>{{ $conversation->last_message_at?->diffForHumans() }}</span>
                        @if ($conversation->developer_handling)
                            <span class="rounded bg-violet-100 px-1.5 py-0.5 font-semibold text-violet-700 dark:bg-violet-500/20 dark:text-violet-300">{{ __('Developer') }}</span>
                        @endif
                    </div>
                </button>
            @empty
                <p class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-400 dark:border-slate-700 dark:text-slate-500">{{ __('Belum ada percakapan.') }}</p>
            @endforelse
        </div>
    </div>

    <!-- Selected conversation -->
    <div class="lg:col-span-2">
        @if ($selected)
            <div class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-4 py-3 dark:border-slate-700">
                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $selected->displayName() }} @if ($selected->user)<span class="font-normal text-slate-400">&lt;{{ $selected->user->email }}&gt;</span>@endif</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            @if ($selected->isGuest())
                                {{ __('Pengunjung landing page (belum punya akun / belum login)') }}
                            @else
                                {{ $selected->store?->name ?? '-' }} &middot; {{ $selected->user->role }}
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($selected->developer_handling)
                            <button type="button" wire:click="returnToAi"
                                class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                {{ __('Kembalikan ke AI') }}
                            </button>
                        @else
                            <span class="rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">{{ __('AI aktif') }}</span>
                        @endif
                        @if ($selected->isOpen())
                            <button type="button" wire:click="close"
                                class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:border-rose-900 dark:text-rose-400 dark:hover:bg-rose-500/10">
                                {{ __('Tutup') }}
                            </button>
                        @else
                            <button type="button" wire:click="reopen"
                                class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                {{ __('Buka lagi') }}
                            </button>
                        @endif
                    </div>
                </div>

                <div class="max-h-[28rem] min-h-[16rem] space-y-3 overflow-y-auto bg-slate-50 p-4 dark:bg-slate-950">
                    @foreach ($selected->messages as $chat)
                        @php $fromUser = $chat->sender_type === 'user'; @endphp
                        <div wire:key="inbox-msg-{{ $chat->id }}" class="flex {{ $fromUser ? 'justify-start' : 'justify-end' }}">
                            <div class="max-w-[80%]">
                                <p class="mb-0.5 text-[11px] font-medium text-slate-400 dark:text-slate-500 {{ $fromUser ? '' : 'text-right' }}">
                                    {{ match ($chat->sender_type) {
                                        'user' => $selected->displayName(),
                                        'developer' => __('Developer').($chat->sender ? ' ('.$chat->sender->name.')' : ''),
                                        'ai' => __('Asisten AI'),
                                        default => __('Sistem'),
                                    } }}
                                    &middot; {{ $chat->created_at->format('d M H:i') }}
                                </p>
                                <div class="whitespace-pre-line break-words rounded-2xl px-3 py-2 text-sm shadow-sm {{ $fromUser ? 'rounded-tl-sm bg-white text-slate-700 dark:bg-slate-800 dark:text-slate-200' : ($chat->sender_type === 'developer' ? 'rounded-tr-sm bg-violet-600 text-white' : ($chat->sender_type === 'ai' ? 'rounded-tr-sm bg-brand-600 text-white' : 'rounded-tr-sm bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300')) }}">{{ $chat->body }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <form wire:submit="sendReply" class="border-t border-slate-200 p-3 dark:border-slate-700">
                    @error('reply')
                        <p class="mb-2 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                    <div class="flex gap-2">
                        <textarea wire:model="reply" rows="2" maxlength="2000" placeholder="{{ __('Balas sebagai developer (AI berhenti menjawab di chat ini)...') }}"
                            class="min-w-0 flex-1 rounded-lg border-slate-200 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-100"></textarea>
                        <button type="submit" class="shrink-0 self-end rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700">{{ __('Kirim') }}</button>
                    </div>
                </form>
            </div>
        @else
            <div class="flex h-64 items-center justify-center rounded-2xl border border-dashed border-slate-300 text-sm text-slate-400 dark:border-slate-700 dark:text-slate-500">
                {{ __('Pilih percakapan di sebelah kiri untuk membaca dan membalas.') }}
            </div>
        @endif
    </div>
</div>
</div>
