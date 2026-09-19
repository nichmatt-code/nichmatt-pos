<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $this->address,
            'birthdate' => $this->birthdate?->format('Y-m-d'),
            'age' => $this->age(),
            'notes' => $this->notes,
            // Cuma ada kalau query-nya sudah `withCount('transactions')` -
            // endpoint pencarian cepat di keranjang tidak memuat ini.
            'transactions_count' => $this->whenCounted('transactions'),
        ];
    }
}
