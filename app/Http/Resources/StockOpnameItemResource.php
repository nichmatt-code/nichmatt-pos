<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockOpnameItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'inventory_item_id' => $this->inventory_item_id,
            'item_name' => $this->item_name,
            'unit' => $this->unit,
            'system_qty' => $this->system_qty,
            'counted_qty' => $this->counted_qty,
            'difference' => $this->difference(),
        ];
    }
}
