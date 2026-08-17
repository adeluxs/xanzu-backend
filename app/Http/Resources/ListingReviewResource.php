<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingReviewResource extends JsonResource
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
            'name' => $this->buyer?->username ?? $this->buyer?->full_name ?? null,
            'avatar' => $this->buyer?->avatar ? $this->buyer->avatar_path : null,
            'rating' => (int) $this->rating,
            'review' => $this->review,
            'created' => $this->created_at?->diffForHumans(),
            'attachments' => $this->when($this->attachments, fn() => collect($this->attachments)->map(fn($a) => $a ? asset($a) : null)->filter()->values()->all()),
            'reply' => $this->whenLoaded('reply', fn() => new self($this->reply)),
        ];
    }
}
