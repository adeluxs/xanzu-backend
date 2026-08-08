<?php

namespace App\Http\Resources;

use App\Http\Controllers\Api\HomeScreenController;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public $fullData = false; // To control how much data to return (e.g. for listing vs details)

    public function toArray(Request $request): array
    {
        if ($request->has('fullData')) {
            $this->fullData = $request->boolean('fullData');
        }

        $reviewCountsByRating = [];
        $totalApprovedReviews = 0;
        if ($this->fullData) {
            // Only return five aggregate rows instead of hydrating every review.
            $groupedCounts = $this->approvedReviews()
                ->selectRaw('rating, COUNT(*) as total')
                ->groupBy('rating')
                ->pluck('total', 'rating');

            $reviewCountsByRating = [
                '1' => (int) ($groupedCounts[1] ?? 0),
                '2' => (int) ($groupedCounts[2] ?? 0),
                '3' => (int) ($groupedCounts[3] ?? 0),
                '4' => (int) ($groupedCounts[4] ?? 0),
                '5' => (int) ($groupedCounts[5] ?? 0),
            ];
            $totalApprovedReviews = array_sum($reviewCountsByRating);
        }

        $hasAttributes = $this->has_attributes && $this->listingAttributes->isNotEmpty();
        $lessQtyAttr = $hasAttributes
            ? $this->listingAttributes->where('final_price', '>', 0)->sortBy('quantity')->first()
            : null;
        $discountType = $this->discount_type;
        $discountAmount = $this->discount_value;
        $disCountText = $discountType === 'percentage' ? (floatval($discountAmount) . '%') : (setting('currency_symbol', 'global') . floatval($discountAmount));

        return [
            'id' => $this->id,
            'product_name' => $this->product_name,
            'redirect_url' => $this->provider_id ? $this->product_url : null,
            'description' => $this->when($this->fullData, $this->description),
            'thumbnail' => $this->thumbnail ? asset($this->thumbnail) : null,
            'gallery_images' => $this->when($this->fullData, fn() => \array_merge($this->thumbnail ? [asset($this->thumbnail)] : [], $this->images ? $this->images->map(fn($img) => asset($img->image_path))->toArray() : [])),
            'price' => $this->price,
            'discount_type' => $this->when($this->fullData, $discountType),
            'discount_value' => $this->when($this->fullData, $discountAmount),
            'final_price' => number_format($this->final_price, 2),
            'has_attributes' => $this->when($this->fullData, $hasAttributes),
            'has_discount' => $discountAmount > 0,
            'discount_text' => $discountAmount ? ($disCountText . ' OFF') : null,
            'sold_count' => $this->when($this->fullData, $this->sold_count),
            'quantity' => $hasAttributes ? $lessQtyAttr?->qty : $this->quantity,
            'type' => $this->type,
            'attributes' => $this->whenLoaded('listingAttributes', fn() => $this->listingAttributes->groupBy('group')->map(function ($attributes, $group) {
                return $attributes->map(function ($attribute) {
                    return ListingAttributeResource::make($attribute);
                })->values();
            })),
            'category' => $this->when($this->fullData, fn() => $this->whenLoaded('category', fn() => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ])),
            'review_summary' => $this->when($this->fullData, fn() => app(HomeScreenController::class)->getCachedReviewSummary($this->id)),
            'brand' => $this->when($this->fullData, fn() => $this->whenLoaded('brand', fn() => [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
                'image' => $this->brand->image ? asset($this->brand->image) : null,
            ])),
            'reviews' => $this->when($this->fullData, fn() => [
                'average' => $this->avg_rating,
                'total' => $totalApprovedReviews,
                'count_by_rating' => $reviewCountsByRating,
                'list' => $this->whenLoaded('reviews', fn() => ListingReviewResource::collection($this->reviews)),
            ]),
            'shipping_info' => (function (): array {
                $shipping = $this->shippingConfig();
                return [
                    'charge' => strval($shipping['shipping_charge']),
                    'charge_type' => $shipping['shipping_charge_type'],
                ];
            })(),
        ];
    }

    public static function withDetails($collection, $fullData = false)
    {
        return $collection->map(function ($item) use ($fullData) {
            $resource = new self($item);
            $resource->fullData = $fullData;
            return $resource;
        });
    }

    // Product Name	Date	Type	Amount	Stock	Category	Rating image
    public static function frontend($collection)
    {
        return $collection->map(function ($item) {
            $resource = [];
            $resource['id'] = $item->id;
            $resource['name'] = $item->product_name;
            $resource['date'] = $item->created_at->format('D, M j, Y');
            $resource['type'] = $item->type;
            $resource['amount'] = formatCurrency($item->final_price);
            $resource['stock'] = !$item->isOutOfStock ? true : false;
            $resource['category'] = $item->category ? $item->category?->name : null;
            $resource['rating'] = $item->avg_rating;
            $resource['image'] = $item->thumbnail ? asset($item->thumbnail) : null;
            return $resource;
        });
    }

}
