<?php

namespace App\Http\Resources;

use App\Services\ThermalReceiptFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_no' => $this->transaction_no,
            'customer_name' => $this->customer_name,
            'note' => $this->note,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'coupon_discount_amount' => $this->coupon_discount_amount,
            'tax_amount' => $this->tax_amount,
            'service_charge_amount' => $this->service_charge_amount,
            'total' => $this->total,
            'payment_method' => $this->payment_method,
            'paid_amount' => $this->paid_amount,
            'change_amount' => $this->change_amount,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'items' => TransactionItemResource::collection($this->whenLoaded('items')),
            // Teks struk siap-cetak (format sama dengan yang dikirim ke
            // printer thermal Bluetooth di versi web), supaya aplikasi
            // mobile bisa mencetak lewat dialog print bawaan OS
            // (AirPrint/Android print service) tanpa perlu tahu detail
            // ESC/POS-nya sendiri.
            'receipt_lines' => ThermalReceiptFormatter::forTransaction($this->resource),
        ];
    }
}
