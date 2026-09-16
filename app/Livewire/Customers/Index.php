<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public bool $showFormModal = false;

    public string $search = '';

    public ?int $editingId = null;

    public string $name = '';

    public string $phone = '';

    public string $address = '';

    public string $notes = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function createCustomer(): void
    {
        $this->reset(['editingId', 'name', 'phone', 'address', 'notes']);
        $this->showFormModal = true;
    }

    public function editCustomer(int $customerId): void
    {
        $customer = Customer::findOrFail($customerId);

        $this->editingId = $customer->id;
        $this->name = $customer->name;
        $this->phone = $customer->phone;
        $this->address = (string) $customer->address;
        $this->notes = (string) $customer->notes;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $storeId = Auth::user()->store_id;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required', 'string', 'max:50',
                Rule::unique('customers', 'phone')->where('store_id', $storeId)->ignore($this->editingId),
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['address'] = $validated['address'] !== '' ? $validated['address'] : null;
        $validated['notes'] = $validated['notes'] !== '' ? $validated['notes'] : null;

        if ($this->editingId) {
            Customer::findOrFail($this->editingId)->update($validated);
        } else {
            Customer::create($validated);
        }

        $this->showFormModal = false;
    }

    public function delete(int $customerId): void
    {
        Customer::findOrFail($customerId)->delete();
    }

    public function render(): View
    {
        return view('livewire.customers.index', [
            'customers' => Customer::query()
                ->withCount('transactions')
                ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%")
                ))
                ->orderBy('name')
                ->paginate(15),
        ]);
    }
}
