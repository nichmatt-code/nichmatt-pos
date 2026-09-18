<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'category_id' => $this->category_id,
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price' => $this->price,
            'stock_qty' => $this->stock_qty,
            'unit' => $this->unit,
            'image_url' => $this->imageUrl(),
            'is_unlimited_stock' => $this->is_unlimited_stock,
            'is_available' => $this->isAvailable(),
        ];
    }
}
