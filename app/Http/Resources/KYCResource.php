<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KYCResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fieldsJson = json_decode($this->fields, true) ?? [];
        $this->icon = $this->icon ? asset($this->icon) : null;
        $kycFields = [];
        foreach ($fieldsJson as $key => $value) {
            $kycFields[] = [
                'name' => $value['name'],
                'type' => $value['type'],
                'validation' => $value['validation'],
                'title' => $value['title'] ?? null,
                'description' => $value['description'] ?? null,
                'instruction_image' => isset($value['instruction_image']) && file_exists(base_path('assets/' . $value['instruction_image'])) ? asset($value['instruction_image']) : null,
                'id' => $key,
            ];
        }

        return $this->only(['id', 'name', 'description']) + [
            'icon' => $this->icon,
            'fields' => $kycFields,
        ];
    }
}
