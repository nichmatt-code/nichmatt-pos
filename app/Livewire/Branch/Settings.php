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

    public string $logoSource = Store::LOGO_SOURCE_POS;

    public bool $showProductImages = true;

    public bool $allowPriceEdit = false;

    public string $taxPercent = '0';

    public string $serviceChargePercent = '0';

    public bool $midtransPaymentEnabled = false;

    public string $midtransServerKey = '';

    public string $midtransClientKey = '';

    public bool $midtransIsProduction = false;

    public bool $hasMidtransServerKey = false;

    public function mount(): void
    {
        $store = Auth::user()->store;

        $this->name = $store->name;
        $this->address = (string) $store->address;
        $this->phone = (string) $store->phone;
        $this->receiptFormat = $store->receipt_format;
        $this->existingLogoUrl = $store->logoUrl();
        $this->logoSource = $store->logo_source;
        $this->showProductImages = $store->show_product_images;
        $this->allowPriceEdit = $store->allow_price_edit;
        $this->taxPercent = (string) $store->tax_percent;
        $this->serviceChargePercent = (string) $store->service_charge_percent;
        $this->midtransPaymentEnabled = $store->midtrans_payment_enabled;
        $this->midtransClientKey = (string) $store->midtrans_client_key;
        $this->midtransIsProduction = $store->midtrans_is_production;
        $this->hasMidtransServerKey = (bool) $store->midtrans_server_key;
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
        $validated = $this->validate([
            'logo' => ['nullable', 'image', 'max:2048'],
            'logoSource' => ['required', 'in:'.Store::LOGO_SOURCE_POS.','.Store::LOGO_SOURCE_STORE],
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

        $store->update(['logo_source' => $validated['logoSource']]);

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

    /**
     * Save this store's own Midtrans merchant credentials so its POS can
     * take QRIS payments that settle directly into the store's own
     * account, instead of the platform's. The server key is write-only:
     * once saved it's never sent back to the browser, and an untouched
     * field on save keeps the previously stored key rather than clearing it.
     */
    public function saveMidtransSettings(): void
    {
        $validated = $this->validate([
            'midtransPaymentEnabled' => ['boolean'],
            'midtransServerKey' => ['nullable', 'string', 'max:255'],
            'midtransClientKey' => ['nullable', 'string', 'max:255'],
            'midtransIsProduction' => ['boolean'],
        ]);

        $store = Auth::user()->store;

        if ($validated['midtransPaymentEnabled'] && $validated['midtransServerKey'] === '' && ! $store->midtrans_server_key) {
            $this->addError('midtransServerKey', 'Server Key wajib diisi untuk mengaktifkan pembayaran online.');

            return;
        }

        $store->update([
            'midtrans_payment_enabled' => $validated['midtransPaymentEnabled'],
            'midtrans_server_key' => $validated['midtransServerKey'] !== '' ? $validated['midtransServerKey'] : $store->midtrans_server_key,
            'midtrans_client_key' => $validated['midtransClientKey'] !== '' ? $validated['midtransClientKey'] : null,
            'midtrans_is_production' => $validated['midtransIsProduction'],
        ]);

        $this->midtransServerKey = '';
        $this->hasMidtransServerKey = (bool) $store->fresh()->midtrans_server_key;

        $this->dispatch('midtrans-settings-updated');
    }

    public function render(): View
    {
        return view('livewire.branch.settings', [
            'store' => Auth::user()->store,
        ]);
    }
}
