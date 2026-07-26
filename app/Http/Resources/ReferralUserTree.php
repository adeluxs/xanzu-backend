<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReferralUserTree extends JsonResource
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
            'avatar' => $this->avatar_path,
            'full_name' => $this->full_name,
            'status' => $this->status,
            'referral_profit' => formatCurrency($this->totalReferralProfit()),
            'created_at' => $this->created_at,
            'referrals' => self::collection($this->whenLoaded('referrals')),
        ];

        return $data;
    }
}
