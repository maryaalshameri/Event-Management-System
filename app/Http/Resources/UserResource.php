<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * تحويل المورد إلى مصفوفة.
     */
     public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'avatar' => $this->avatar_url ? asset('storage/' . $this->avatar) : null,
            'role' => $this->role,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'events' => EventResource::collection($this->whenLoaded('events')),
            'orders' => OrderResource::collection($this->whenLoaded('orders')),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}