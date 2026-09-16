<?php

namespace App\Livewire\Branch;

use App\Models\Store;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Settings extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $address = '';

    public string $phone = '';

    public string $receiptFormat = Store::RECEIPT_FORMAT_THERMAL;

    public mixed $logo = null;

    public ?string $existingLogoUrl = null;

    public bool $removeExistingLogo = false;

    public bool $showProductImages = true;

    public bool $allowPriceEdit = false;

    public string $taxPercent = '0';

    public string $serviceChargePercent = '0';

    public function mount(): void
    {
        $store = Auth::user()->store;

        $this->name = $store->name;
        $this->address = (string) $store->address;
        $this->phone = (string) $store->phone;
        $this->receiptFormat = $store->receipt_format;
        $this->existingLogoUrl = $store->logoUrl();
        $this->showProductImages = $store->show_product_images;
        $this->allowPriceEdit = $store->allow_price_edit;
        $this->taxPercent = (string) $store->tax_percent;
        $this->serviceChargePercent = (string) $store->service_charge_percent;
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

    public function removeLogo(): void
    {
        $this->logo = null;
        $this->existingLogoUrl = null;
        $this->removeExistingLogo = true;
    }

    public function saveLogo(): void
    {
        $this->validate([
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $store = Auth::user()->store;

        if ($this->logo) {
            if ($store->logo_path) {
                Storage::disk('public')->delete($store->logo_path);
            }
            $store->update(['logo_path' => $this->logo->store('logos', 'public')]);
        } elseif ($this->removeExistingLogo && $store->logo_path) {
            Storage::disk('public')->delete($store->logo_path);
            $store->update(['logo_path' => null]);
        }

        $this->logo = null;
        $this->removeExistingLogo = false;
        $this->existingLogoUrl = $store->fresh()->logoUrl();

        $this->dispatch('logo-updated');
    }

    public function saveReceiptFormat(): void
    {
        $validated = $this->validate([
            'receiptFormat' => ['required', 'in:'.Store::RECEIPT_FORMAT_THERMAL.','.Store::RECEIPT_FORMAT_PDF],
        ]);

        Auth::user()->store->update(['receipt_format' => $validated['receiptFormat']]);

        $this->dispatch('receipt-format-updated');
    }

    public function savePosSettings(): void
    {
        $validated = $this->validate([
            'showProductImages' => ['boolean'],
            'allowPriceEdit' => ['boolean'],
            'taxPercent' => ['required', 'integer', 'min:0', 'max:100'],
            'serviceChargePercent' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        Auth::user()->store->update([
            'show_product_images' => $validated['showProductImages'],
            'allow_price_edit' => $validated['allowPriceEdit'],
            'tax_percent' => $validated['taxPercent'],
            'service_charge_percent' => $validated['serviceChargePercent'],
        ]);

        $this->dispatch('pos-settings-updated');
    }

    public function render(): View
    {
        return view('livewire.branch.settings', [
            'store' => Auth::user()->store,
        ]);
    }
}
