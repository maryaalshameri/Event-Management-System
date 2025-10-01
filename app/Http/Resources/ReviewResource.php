<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * تحويل المورد إلى مصفوفة.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'rating_stars' => $this->rating_stars,
            'has_comment' => $this->hasComment(),
            'user' => new UserResource($this->whenLoaded('user')),
            'event' => new EventResource($this->whenLoaded('event')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}