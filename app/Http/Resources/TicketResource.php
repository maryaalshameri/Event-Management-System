<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * تحويل المورد إلى مصفوفة.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'pdf_path' => $this->pdf_path ? asset('storage/' . $this->pdf_path) : null,
            'qr_path' => $this->qr_path ? asset('storage/' . $this->qr_path) : null,
            'used_at' => $this->used_at,
            'is_used' => $this->isUsed(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}