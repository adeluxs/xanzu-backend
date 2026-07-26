<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
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
            'uuid' => $this->uuid,
            'user' => [
                'name' => $this->user->first_name.' '.$this->user->last_name,
                'email' => $this->user->email,
            ],
            'title' => $this->title,
            'message' => $this->message,
            'priority' => ucfirst($this->priority),
            'status' => ucfirst($this->status),
            'last_reply' => $this->messages->first()?->created_at->diffForHumans(),
            'attachments' => is_array($this->attachments) ? collect($this->attachments)->map(function ($attachment) {
                return asset($attachment);
            }) : [],
            'created_at' => now()->parse($this->created_at)->format('Y-m-d h:i:s a'),
        ];
    }
}
