<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'message' => $this->message,
            'avatar' => $this->getAvatarPath(),
            'name' => $this->user->first_name . ' ' . $this->user->last_name,
            'email' => $this->user->email,
            'is_admin' => $this->model == 'admin',
            'attachments' => collect($this->attachments)->map(function ($attachment) {
                return asset($attachment);
            }),
            'created_at' => $this->created_at->diffForHumans(),
            'parent' => $this->whenLoaded('parent', function () {
                return [
                    'id' => $this->parent->id,
                    'message' => $this->parent->message,
                    'is_admin' => $this->parent->model == 'admin',
                ];
            }),
        ];

        return $data;
    }

    private function getAvatarPath()
    {
        if ($this->model == 'admin') {
            return asset('front/images/user.jpg');
        }

        return $this->user->avatar !== null && file_exists(base_path('assets/' . $this->user->avatar)) ? asset($this->user->avatar) : asset('front/images/user.jpg');
    }
}
