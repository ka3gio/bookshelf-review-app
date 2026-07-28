<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
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
            'user' => [
                'user_id' => $this->user_id,
                'user_name' => $this->user->name,
            ],
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'published_date' => $this->published_date,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'genres' => GenreResource::collection($this->whenLoaded('genres')),
            'average_rating' => $this->whenAggregated('reviews', 'rating', 'avg'),
            'review_count' => $this->whenCounted('reviews'),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}
