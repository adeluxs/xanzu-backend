<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public $withChildren = false;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => $this->image ? asset($this->image) : null,
            'description' => $this->description,
            'children' => $this->when($this->withChildren, self::withChildren($this->children, false)),
        ];
    }

    public static function withChildren($categories, $withChildren = true)
    {
        return $categories->map(function ($category) use ($withChildren) {
            $data = (new self($category))->toArray(request());
            if ($withChildren && $category->children->isNotEmpty()) {
                $data['children'] = self::collection($category->children);
            } else {
                $data['children'] = [];
            }

            return $data;
        });
    }
}
