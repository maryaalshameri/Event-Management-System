<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'capacity' => $this->capacity,
            'available_seats' => $this->available_seats,
            'category' => $this->category,
            'price' => $this->price,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'status' => $this->status,
            'organizer_id' => $this->organizer_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'tickets_sold' => $this->whenLoaded('orders', function() {
                return $this->orders()->where('payment_status', 'completed')->count();
            }),
            'organizer' => $this->whenLoaded('organizer', function() {
                return [
                    'id' => $this->organizer->id,
                    'name' => $this->organizer->name,
                    'email' => $this->organizer->email
                ];
            })
        ];
    }
}