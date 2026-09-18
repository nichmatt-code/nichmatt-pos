<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A lighter version of TransactionResource for the history LIST - leaves
 * out items/receipt_lines (only needed once you open one transaction),
 * so scrolling a long history doesn't recompute a full receipt per row.
 */
class TransactionSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_no' => $this->transaction_no,
            'customer_name' => $this->customer_name,
            'total' => $this->total,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
