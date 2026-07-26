<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LanguageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->except(['created_at', 'updated_at']);
        $data['status'] = boolval($this->status);
        $data['is_default'] = boolval($this->is_default);
        $data['is_rtl'] = boolval($this->is_rtl);

        return $data;
    }
}
