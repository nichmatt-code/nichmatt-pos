<?php

namespace App\Livewire\Units;

use App\Livewire\Concerns\Sortable;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use Sortable, WithPagination;

    public string $name = '';

    public string $search = '';

    public ?int $editingId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $storeId = Auth::user()->store_id;

        $validated = $this->validate([
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('units', 'name')->where('store_id', $storeId)->ignore($this->editingId),
            ],
        ]);

        if ($this->editingId) {
            Unit::findOrFail($this->editingId)->update($validated);
        } else {
            Unit::create($validated);
        }

        $this->reset(['name', 'editingId']);
    }

    public function edit(int $unitId): void
    {
        $unit = Unit::findOrFail($unitId);

        $this->editingId = $unit->id;
        $this->name = $unit->name;
    }

    public function cancelEdit(): void
    {
        $this->reset(['name', 'editingId']);
    }

    public function delete(int $unitId): void
    {
        Unit::findOrFail($unitId)->delete();
    }

    public function render(): View
    {
        return view('livewire.units.index', [
            'units' => Unit::query()
                ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
                ->orderBy($this->sortField ?: 'name', $this->sortDirection)
                ->paginate(15),
        ]);
    }
}
