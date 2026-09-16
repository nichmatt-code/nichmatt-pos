<?php

namespace App\Livewire\Branch;

use App\Models\Store;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Settings extends Component
{
    public string $name = '';

    public string $address = '';

    public string $phone = '';

    public string $receiptFormat = Store::RECEIPT_FORMAT_THERMAL;

    public function mount(): void
    {
        $store = Auth::user()->store;

        $this->name = $store->name;
        $this->address = (string) $store->address;
        $this->phone = (string) $store->phone;
        $this->receiptFormat = $store->receipt_format;
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

    public function saveReceiptFormat(): void
    {
        $validated = $this->validate([
            'receiptFormat' => ['required', 'in:'.Store::RECEIPT_FORMAT_THERMAL.','.Store::RECEIPT_FORMAT_PDF],
        ]);

        Auth::user()->store->update(['receipt_format' => $validated['receiptFormat']]);

        $this->dispatch('receipt-format-updated');
    }

    public function render(): View
    {
        return view('livewire.branch.settings', [
            'store' => Auth::user()->store,
        ]);
    }
}
