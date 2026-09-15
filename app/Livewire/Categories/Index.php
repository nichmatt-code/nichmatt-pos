<?php

namespace App\Livewire\Categories;

use App\Models\Category;
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
            Category::findOrFail($this->editingId)->update($validated);
        } else {
            Category::create($validated);
        }

        $this->reset(['name', 'editingId']);
    }

    public function edit(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);

        $this->editingId = $category->id;
        $this->name = $category->name;
    }

    public function cancelEdit(): void
    {
        $this->reset(['name', 'editingId']);
    }

    public function delete(int $categoryId): void
    {
        Category::findOrFail($categoryId)->delete();
    }

    public function render(): View
    {
        return view('livewire.categories.index', [
            'categories' => Category::query()->withCount('products')->orderBy('name')->get(),
        ]);
    }
}
