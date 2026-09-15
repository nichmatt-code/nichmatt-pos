<div class="space-y-6">
    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900">{{ $editingId ? 'Edit Kategori' : 'Tambah Kategori' }}</h3>

        <form wire:submit="save" class="mt-4 flex flex-wrap items-start gap-3">
            <div class="flex-1 min-w-[200px]">
                <x-text-input wire:model="name" type="text" class="block w-full" placeholder="Nama kategori" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <x-primary-button type="submit">{{ $editingId ? 'Update' : 'Tambah' }}</x-primary-button>

            @if ($editingId)
                <button type="button" wire:click="cancelEdit" class="text-sm text-gray-500 hover:text-gray-700 mt-2">Batal</button>
            @endif
        </form>
    </div>

    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead>
                <tr class="text-left text-gray-500">
                    <th class="px-6 py-3">Nama</th>
                    <th class="px-6 py-3">Jumlah Produk</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($categories as $category)
                    <tr wire:key="category-{{ $category->id }}">
                        <td class="px-6 py-3 text-gray-900">{{ $category->name }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ $category->products_count }}</td>
                        <td class="px-6 py-3 text-right space-x-3">
                            <button wire:click="edit({{ $category->id }})" class="text-indigo-600 hover:text-indigo-800">Edit</button>
                            <button wire:click="delete({{ $category->id }})" wire:confirm="Hapus kategori ini?" class="text-red-600 hover:text-red-800">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-6 text-center text-gray-500">Belum ada kategori.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
