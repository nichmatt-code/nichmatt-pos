<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this->product_id,
            'package_id' => $this->package_id,
            'product_name' => $this->product_name,
            'price' => $this->price,
            'qty' => $this->qty,
            'note' => $this->note,
            'subtotal' => $this->subtotal,
        ];
    }
}
