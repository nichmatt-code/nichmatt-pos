<div class="space-y-6">
    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6">
        <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $editingId ? __('Edit Tag') : __('Tambah Tag') }}</h3>

        <form wire:submit="save" class="mt-4 flex flex-wrap items-start gap-3">
            <div class="flex-1 min-w-[200px]">
                <x-text-input wire:model="name" type="text" class="block w-full" placeholder="Nama tag" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $editingId ? __('Update') : __('Tambah') }}</span>
                <span wire:loading wire:target="save">{{ __('Menyimpan...') }}</span>
            </x-primary-button>

            @if ($editingId)
                <button type="button" wire:click="cancelEdit" class="text-sm text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 mt-2.5">{{ __('Batal') }}</button>
            @endif
        </form>
    </div>

    <div class="relative w-full sm:w-72">
        <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
        <x-text-input wire:model.live.debounce.300ms="search" type="text" class="w-full pl-10" placeholder="Cari tag..." />
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500 bg-slate-50/70 dark:bg-slate-800/40">
                    <x-th-sort field="name" label="{{ __('Nama') }}" :sortField="$sortField" :sortDirection="$sortDirection" />
                    <x-th-sort field="products_count" label="{{ __('Jumlah Produk') }}" :sortField="$sortField" :sortDirection="$sortDirection" />
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($tags as $tag)
                    <tr wire:key="tag-{{ $tag->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                        <td class="px-6 py-3.5 text-slate-900 dark:text-slate-100 font-medium">{{ $tag->name }}</td>
                        <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400">{{ $tag->products_count }}</td>
                        <td class="px-6 py-3.5 text-right space-x-3">
                            <button wire:click="edit({{ $tag->id }})" class="text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300 font-medium">{{ __('Edit') }}</button>
                            <button wire:click="delete({{ $tag->id }})" wire:confirm="Hapus tag ini?" class="text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300 font-medium">{{ __('Hapus') }}</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">{{ __('Belum ada tag.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800">
            {{ $tags->links() }}
        </div>
    </div>
</div>
