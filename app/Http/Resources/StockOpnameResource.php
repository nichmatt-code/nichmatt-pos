<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockOpnameResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'status' => $this->status,
            'note' => $this->note,
            'created_at' => $this->created_at,
            'completed_at' => $this->completed_at,
            'items_count' => $this->whenCounted('items'),
            'items' => StockOpnameItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
