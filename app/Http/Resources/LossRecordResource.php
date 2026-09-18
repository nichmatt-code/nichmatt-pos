<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LossRecordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'loss_no' => $this->loss_no,
            'reason' => $this->reason,
            'total_cost_value' => $this->total_cost_value,
            'created_at' => $this->created_at,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'qty' => $item->qty,
                'cost_price' => $item->cost_price,
                'subtotal_cost' => $item->subtotal_cost,
            ])),
        ];
    }
}
