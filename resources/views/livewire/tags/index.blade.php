<div class="space-y-6">
    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6">
        <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $editingId ? __('Edit Tag') : __('Tambah Tag') }}</h3>

        <form wire:submit="save" class="mt-4 flex flex-wrap items-start gap-3">
            <div class="flex-1 min-w-[200px]">
                <x-text-input wire:model="name" type="text" class="block w-full" placeholder="Nama tag" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <x-primary-button type="submit">{{ $editingId ? __('Update') : __('Tambah') }}</x-primary-button>

            @if ($editingId)
                <button type="button" wire:click="cancelEdit" class="text-sm text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 mt-2.5">{{ __('Batal') }}</button>
            @endif
        </form>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500 bg-slate-50/70 dark:bg-slate-800/40">
                    <th class="px-6 py-3">{{ __('Nama') }}</th>
                    <th class="px-6 py-3">{{ __('Jumlah Produk') }}</th>
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
    </div>
</div>
