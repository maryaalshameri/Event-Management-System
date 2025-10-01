<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketTypeResource extends JsonResource
{
    /**
     * تحويل المورد إلى مصفوفة.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'price' => (float) $this->price,
            'quantity' => $this->quantity,
            'sold' => $this->sold,
            'available_quantity' => $this->available_quantity,
            'is_sold_out' => $this->isSoldOut(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}