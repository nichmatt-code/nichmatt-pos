<div class="space-y-6">
    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 shadow-card rounded-2xl p-6">
        <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $editingId ? __('Edit Kategori') : __('Tambah Kategori') }}</h3>

        <form wire:submit="save" class="mt-4 flex flex-wrap items-start gap-3">
            <div class="flex-1 min-w-[200px]">
                <x-text-input wire:model="name" type="text" class="block w-full" placeholder="Nama kategori" />
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
        <x-text-input wire:model.live.debounce.300ms="search" type="text" class="w-full pl-10" placeholder="Cari kategori..." />
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
                @forelse ($categories as $category)
                    <tr wire:key="category-{{ $category->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                        <td class="px-6 py-3.5 text-slate-900 dark:text-slate-100 font-medium">{{ $category->name }}</td>
                        <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400">{{ $category->products_count }}</td>
                        <td class="px-6 py-3.5 text-right">
                            <div class="inline-flex items-center gap-1">
                                <x-icon-button wire:click="edit({{ $category->id }})" title="{{ __('Edit') }}" color="brand">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                </x-icon-button>
                                <x-icon-button wire:click="delete({{ $category->id }})" wire:confirm="Hapus kategori ini?" title="{{ __('Hapus') }}" color="rose">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                </x-icon-button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">{{ __('Belum ada kategori.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800">
            {{ $categories->links() }}
        </div>
    </div>
</div>
