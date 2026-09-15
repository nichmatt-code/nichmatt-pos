<?php

namespace App\Livewire\Branch;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Settings extends Component
{
    public string $name = '';

    public string $address = '';

    public string $phone = '';

    public function mount(): void
    {
        $store = Auth::user()->store;

        $this->name = $store->name;
        $this->address = (string) $store->address;
        $this->phone = (string) $store->phone;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        Auth::user()->store->update($validated);

        $this->dispatch('branch-updated');
    }

    public function render(): View
    {
        return view('livewire.branch.settings', [
            'store' => Auth::user()->store,
        ]);
    }
}
