<div class="fixed bottom-5 right-5 z-50 flex flex-col items-end"
    x-data
    x-on:support-scroll.window="$nextTick(() => { const el = document.getElementById('support-chat-scroll'); if (el) el.scrollTop = el.scrollHeight; })">
    @if ($open)
        <div wire:poll.5s="refresh"
            class="mb-3 w-[calc(100vw-2.5rem)] sm:w-96 flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between bg-gradient-to-r from-brand-500 to-brand-700 px-4 py-3 text-white">
                <div>
                    <p class="text-sm font-semibold">{{ __('Bantuan NichmattPOS') }}</p>
                    <p class="text-xs text-white/80">{{ __('Tanya cara pakai aplikasi, dijawab AI & tim developer') }}</p>
                </div>
                <button type="button" wire:click="toggle" class="rounded-lg p-1 hover:bg-white/20" aria-label="{{ __('Tutup') }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div id="support-chat-scroll" class="h-80 space-y-3 overflow-y-auto bg-slate-50 p-4 dark:bg-slate-950">
                <div class="max-w-[85%] rounded-2xl rounded-tl-sm bg-white px-3 py-2 text-sm text-slate-700 shadow-sm dark:bg-slate-800 dark:text-slate-200">
                    @auth
                        {{ __('Halo! Ada yang bisa dibantu? Tanyakan apa saja soal aplikasi, misalnya "cara tambah produk" atau "cara stock opname".') }}
                    @else
                        {{ __('Halo! Mau tahu fitur, harga, atau cara mencoba NichmattPOS? Tanyakan saja di sini.') }}
                    @endauth
                </div>

                @foreach ($messages as $chat)
                    @php $mine = $chat->sender_type === 'user'; @endphp
                    <div wire:key="support-msg-{{ $chat->id }}" class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[85%]">
                            @unless ($mine)
                                <p class="mb-0.5 ml-1 text-[11px] font-medium text-slate-400 dark:text-slate-500">
                                    {{ match ($chat->sender_type) {
                                        'developer' => __('Developer'),
                                        'ai' => __('Asisten AI'),
                                        default => __('Sistem'),
                                    } }}
                                </p>
                            @endunless
                            <div class="whitespace-pre-line break-words rounded-2xl px-3 py-2 text-sm shadow-sm {{ $mine ? 'rounded-tr-sm bg-brand-600 text-white' : ($chat->sender_type === 'developer' ? 'rounded-tl-sm bg-violet-50 text-violet-900 dark:bg-violet-500/15 dark:text-violet-100' : 'rounded-tl-sm bg-white text-slate-700 dark:bg-slate-800 dark:text-slate-200') }}">{{ $chat->body }}</div>
                        </div>
                    </div>
                @endforeach

                <div wire:loading wire:target="send" class="text-xs italic text-slate-400 dark:text-slate-500">
                    {{ __('Asisten sedang mengetik...') }}
                </div>

                @if ($conversation && ! $conversation->isOpen())
                    <p class="text-center text-xs text-slate-400 dark:text-slate-500">{{ __('Percakapan ditutup. Kirim pesan untuk memulai yang baru.') }}</p>
                @endif
            </div>

            <form wire:submit="send" class="border-t border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                @error('message')
                    <p class="mb-2 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
                @error('guestName')
                    <p class="mb-2 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
                @if ($askForName)
                    <input type="text" wire:model="guestName" maxlength="60" autocomplete="name"
                        placeholder="{{ __('Nama Anda (opsional)') }}"
                        class="mb-2 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-100" />
                @endif
                <div class="flex gap-2">
                    <input type="text" wire:model="message" maxlength="1000" autocomplete="off"
                        placeholder="{{ __('Tulis pertanyaan...') }}"
                        class="min-w-0 flex-1 rounded-lg border-slate-200 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-100" />
                    <button type="submit" wire:loading.attr="disabled" wire:target="send"
                        class="shrink-0 rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-60">
                        {{ __('Kirim') }}
                    </button>
                </div>
            </form>
        </div>
    @endif

    <button type="button" wire:click="toggle" title="{{ __('Bantuan') }}"
        class="relative flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-lg transition hover:scale-105 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
        @if ($hasUnread)
            <span class="absolute -right-0.5 -top-0.5 h-4 w-4 rounded-full border-2 border-white bg-rose-500 dark:border-slate-900"></span>
        @endif
    </button>
</div>
