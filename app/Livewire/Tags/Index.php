<?php

namespace App\Livewire\Tags;

use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    public string $name = '';

    public ?int $editingId = null;

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->editingId) {
            Tag::findOrFail($this->editingId)->update($validated);
        } else {
            Tag::create($validated);
        }

        $this->reset(['name', 'editingId']);
    }

    public function edit(int $tagId): void
    {
        $tag = Tag::findOrFail($tagId);

        $this->editingId = $tag->id;
        $this->name = $tag->name;
    }

    public function cancelEdit(): void
    {
        $this->reset(['name', 'editingId']);
    }

    public function delete(int $tagId): void
    {
        Tag::findOrFail($tagId)->delete();
    }

    public function render(): View
    {
        return view('livewire.tags.index', [
            'tags' => Tag::query()->withCount('products')->orderBy('name')->get(),
        ]);
    }
}
