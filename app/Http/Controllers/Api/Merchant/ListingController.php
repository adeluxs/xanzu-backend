<?php

namespace App\Http\Controllers\Api\Merchant;

use BackedEnum;
use App\Enums\ListingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Merchant\StoreListingRequest;
use App\Http\Requests\Api\Merchant\UpdateListingRequest;
use App\Http\Resources\ListingResource;
use App\Models\DeliveryItem;
use App\Models\Listing;
use App\Models\Provider;
use App\Traits\ApiResponse;
use App\Traits\ImageUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ListingController extends Controller
{
    use ApiResponse;
    use ImageUpload;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || $user->user_type !== 'merchant') {
            return $this->validationErrorResponse(__('Invalid merchant account.'));
        }

        $validator = Validator::make($request->all(), [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:all,' . implode(',', array_column(ListingStatus::cases(), 'value'))],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $query = Listing::query()
            ->select([
                'id',
                'product_name',
                'type',
                'delivery_method',
                'category_id',
                'discount_value',
                'discount_type',
                'shipping_charge',
                'shipping_charge_type',
                'price',
                'quantity',
                'status',
            ])

            ->where('seller_id', $user->id)
            ->whereNull('provider_product_id')
            ->with(['category:id,name'])
            ->withCount([
                'deliveryItems as ready_delivery_items_count' => function ($query) {
                    $query->whereNull('order_id')->whereNotNull('data')->where('is_used', 0);
                },
            ])
            ->latest();

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $status = $request->input('status', 'all');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $listings = $query->paginate((int) $request->input('per_page', 20))->through(function ($listing) {
            $listing->setAppends(['final_price']);
            $listing->setAttribute('delivery_item_info', $this->deliveryFlags($listing));

            return $listing;
        });


        return $this->successResponse(
            data: ['listings' => $listings->items()],
            meta: [
                'current_page' => $listings->currentPage(),
                'per_page' => $listings->perPage(),
                'total' => $listings->total(),
                'last_page' => $listings->lastPage(),
            ]
        );
    }

    public function store(StoreListingRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || $user->user_type !== 'merchant') {
            return $this->validationErrorResponse(__('Invalid merchant account.'));
        }

        $hasAttributes = $request->hasAttributes();

        $payload = $request->except(['thumbnail', 'gallery', 'attribute_groups']);
        if ($request->hasFile('thumbnail')) {
            $payload['thumbnail'] = $this->imageUploadTrait($request->file('thumbnail'));
        }

        if ((!$request->quantity || empty($request->quantity)) && $hasAttributes) {
            $payload['quantity'] = 0;
        }


        $payload['seller_id'] = $user->id;
        $payload['is_approved'] = 1;
        $payload['is_flash'] = $request->boolean('is_flash');
        $payload['has_attributes'] = $hasAttributes;
        $payload['discount_value'] = $request->float('discount_value', 0);
        $payload['provider_id'] = Provider::where('user_id', $user->id)->first()?->id;
        $payload['subcategory_id'] = $request->filled('subcategory_id') ? $request->input('subcategory_id') : null;

        $slug = str()->slug((string) $payload['product_name']);
        if (Listing::where('slug', $slug)->exists()) {
            $slug = $slug . '-' . (Listing::max('id') + 1);
        }
        $payload['slug'] = $slug;

        $listing = Listing::create($payload);

        if ($request->hasFile('gallery')) {
            foreach ((array) $request->file('gallery') as $image) {
                $listing->images()->create([
                    'image_path' => $this->imageUploadTrait($image),
                ]);
            }
        }

        if ($hasAttributes) {
            $this->syncAttributes($listing, $this->attributeGroups($request));
            $this->updateListingFromAttributes($listing);
        } else {
            DeliveryItem::createNew($listing->quantity, $listing);
        }

        $listing->load(['category', 'images', 'listingAttributes']);

        return $this->successResponse(
            data: ['listing' => $this->listingDataWithDeliveryFlags($request, $listing)],
            message: __('Listing created successfully!'),
            code: 201
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || $user->user_type !== 'merchant') {
            return $this->validationErrorResponse(__('Invalid merchant account.'));
        }

        $listing = Listing::query()
            ->where('seller_id', $user->id)
            ->with(['category:id', 'images', 'listingAttributes'])
            ->withCount([
                'deliveryItems as ready_delivery_items_count' => function ($query) {
                    $query->whereNull('order_id')->whereNotNull('data')->where('is_used', 0);
                },
            ])
            ->find($id);

        if (!$listing) {
            return $this->notFoundResponse(__('Listing not found.'));
        }


        $listing->thumbnail = $listing->thumbnail ? asset($listing->thumbnail) : null;

        $listing->gallery_images = $listing->images ? $listing->images->transform(function ($image) {
            $image->image_path = asset($image->image_path);
            return $image->image_path;
        }) : [];

        $listing->unsetRelation('images');

        $listing->withCasts([
            'has_attributes' => 'integer',
        ]);

        $listing->except(['created_at', 'updated_at', 'views'], 'sold_count', 'provider_product_id', 'product_url', 'provider_id');

        $listing->setAttribute('delivery_item_info', $this->deliveryFlags($listing));

        return $this->successResponse(data: ['listing' => $listing]);
    }

    public function update(UpdateListingRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || $user->user_type !== 'merchant') {
            return $this->validationErrorResponse(__('Invalid merchant account.'));
        }

        $listing = Listing::query()->where('seller_id', $user->id)->find($id);
        if (!$listing) {
            return $this->notFoundResponse(__('Listing not found.'));
        }

        $hasAttributes = $request->hasAttributes();

        $payload = $request->except(['thumbnail', 'gallery', 'attribute_groups']);

        if ($request->hasFile('thumbnail')) {
            $payload['thumbnail'] = $this->imageUploadTrait($request->file('thumbnail'), $listing->thumbnail);
        }

        $payload['is_flash'] = $request->boolean('is_flash');
        $payload['has_attributes'] = $hasAttributes;
        $payload['provider_id'] = Provider::where('user_id', $user->id)->first()?->id;
        $payload['subcategory_id'] = $request->filled('subcategory_id') ? $request->input('subcategory_id') : null;
        $payload['discount_value'] = $request->float('discount_value', 0);

        if ($listing->product_name !== $request->input('product_name')) {
            $slug = str()->slug((string) $request->input('product_name'));
            if (Listing::where('slug', $slug)->where('id', '!=', $listing->id)->exists()) {
                $slug = $slug . '-' . (Listing::max('id') + 1);
            }
            $payload['slug'] = $slug;
        }

        $oldQuantity = $listing->deliveryItems()->whereNull('order_id')->count();
        $listing->update($payload);

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $image) {
                $listing->images()->create([
                    'image_path' => $this->imageUploadTrait($image),
                ]);
            }
        }

        if ($request->input(('deleted_images'))) {
            foreach ($request->input('deleted_images') as $key => $image) {
                // remove main url part to get the path stored in DB
                $imagePath = str_replace(asset(''), '', $image);
                $this->delete($imagePath);
                $listing->images()->where('image_path', $imagePath)->delete();
            }
        }

        if ($hasAttributes) {
            $this->syncAttributes($listing, $this->attributeGroups($request));
            $this->updateListingFromAttributes($listing);
        } else {
            $listing->listingAttributes()->delete();
            if ($listing->quantity != $oldQuantity) {
                if ($listing->quantity > $oldQuantity) {
                    DeliveryItem::createNew($listing->quantity - $oldQuantity, $listing);
                } else {
                    $listing->deliveryItems()->latest('id')->whereNull('order_id')->take($oldQuantity - $listing->quantity)->delete();
                }
            }
        }

        $listing->load(['category', 'images', 'listingAttributes']);

        return $this->successResponse(
            data: ['listing' => $this->listingDataWithDeliveryFlags($request, $listing)],
            message: __('Listing updated successfully!')
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || $user->user_type !== 'merchant') {
            return $this->validationErrorResponse(__('Invalid merchant account.'));
        }

        $listing = Listing::query()->where('seller_id', $user->id)->with('images')->find($id);
        if (!$listing) {
            return $this->notFoundResponse(__('Listing not found.'));
        }

        $this->delete($listing->thumbnail);
        foreach ($listing->images as $image) {
            $this->delete($image->image_path);
        }

        $listing->deliveryItems()->delete();
        $listing->listingAttributes()->delete();
        $listing->delete();

        return $this->successResponse(message: __('Listing deleted successfully!'));
    }

    public function deliveryItems(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || $user->user_type !== 'merchant') {
            return $this->validationErrorResponse(__('Invalid merchant account.'));
        }

        $validator = Validator::make($request->all(), [
            'status' => ['nullable', 'in:all,missing,ready'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $listing = Listing::query()->where('seller_id', $user->id)->find($id);
        if (!$listing) {
            return $this->notFoundResponse(__('Listing not found.'));
        }

        if (!$this->isDigitalListing($listing)) {
            return $this->validationErrorResponse(__('Delivery items can only be managed for digital listings.'));
        }

        $items = $listing->deliveryItems()
            ->whereNull('order_id')
            ->when($request->input('status') === 'missing', function ($query) {
                $query->whereNull('data');
            })
            ->when($request->input('status') === 'ready', function ($query) {
                $query->whereNotNull('data')->where('is_used', 0);
            })
            ->orderBy('id')
            ->paginate((int) $request->input('per_page', 50));

        return $this->successResponse(
            data: [
                'listing' => $this->deliveryListingSummary($listing),
                'delivery_items' => collect($items->items())
                    ->map(fn(DeliveryItem $item) => $this->serializeDeliveryItem($item))
                    ->values(),
            ],
            meta: [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ]
        );
    }

    public function storeDeliveryItems(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || $user->user_type !== 'merchant') {
            return $this->validationErrorResponse(__('Invalid merchant account.'));
        }

        $validator = Validator::make($request->all(), [
            'delivery_items' => ['required', 'array', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $listing = Listing::query()->where('seller_id', $user->id)->find($id);
        if (!$listing) {
            return $this->notFoundResponse(__('Listing not found.'));
        }

        if (!$this->isDigitalListing($listing)) {
            return $this->validationErrorResponse(__('Delivery items can only be managed for digital listings.'));
        }

        $deliveryItems = $this->normalizeDeliveryItems($request->input('delivery_items', []));
        if (empty($deliveryItems)) {
            return $this->validationErrorResponse(__('Please provide at least one valid delivery item.'));
        }

        $itemsWithoutIds = collect($deliveryItems)->whereNull('id')->values();
        $emptySlotCount = $listing->deliveryItems()->whereNull('order_id')->whereNull('data')->count();
        $unassignedSlotCount = $listing->deliveryItems()->whereNull('order_id')->count();
        $creatableCount = max(0, (int) $listing->quantity - $unassignedSlotCount);
        $availableSlotCount = $emptySlotCount + $creatableCount;

        if ($itemsWithoutIds->count() > $availableSlotCount) {
            return $this->validationErrorResponse(__('Only :count delivery item slot(s) are available for this listing.', [
                'count' => $availableSlotCount,
            ]));
        }

        DB::beginTransaction();

        $updated = collect();

        foreach ($deliveryItems as $item) {
            if ($item['id']) {
                $deliveryItem = $listing->deliveryItems()
                    ->whereNull('order_id')
                    ->where('id', $item['id'])
                    ->first();

                if (!$deliveryItem) {
                    DB::rollBack();

                    return $this->notFoundResponse(__('Delivery item not found.'));
                }
            } else {
                $deliveryItem = $listing->deliveryItems()
                    ->whereNull('order_id')
                    ->whereNull('data')
                    ->oldest('id')
                    ->first();

                if (!$deliveryItem) {
                    $deliveryItem = $listing->deliveryItems()->create([
                        'data' => null,
                        'is_used' => 0,
                    ]);
                }
            }

            $deliveryItem->update([
                'data' => $item['data'],
                'is_used' => 0,
            ]);

            $updated->push($deliveryItem->fresh());
        }

        DB::commit();

        orderService()->deliverReadyWaitingOrdersForListing($listing);
        $listing->refresh();

        return $this->successResponse(
            data: [
                'listing' => $this->deliveryListingSummary($listing),
                'delivery_items' => $updated->map(fn(DeliveryItem $item) => $this->serializeDeliveryItem($item))->values(),
            ],
            message: __('Delivery items updated successfully.')
        );
    }

    private function listingDataWithDeliveryFlags(Request $request, Listing $listing): array
    {
        $resource = new ListingResource($listing);
        $resource->fullData = true;

        return array_merge($resource->toArray($request), $this->deliveryFlags($listing));
    }

    private function deliveryFlags(Listing $listing): array
    {
        $isDeliverable = $this->isDigitalListing($listing);
        $readyDeliveryItemsCount = $this->countReadyDeliveryItems($listing);

        $qty = (int) $listing->quantity;
        $remainingDeliveryCount = $isDeliverable ? max(0, $qty - $readyDeliveryItemsCount) : 0;

        return [
            'is_deliverable' => $isDeliverable,
            'need_to_add_delivery' => $remainingDeliveryCount > 0,
            'remaining_delivery_count' => $remainingDeliveryCount,
        ];
    }

    private function deliveryListingSummary(Listing $listing): array
    {
        return [
            'id' => $listing->id,
            'product_name' => $listing->product_name,
            'type' => $listing->type,
            'delivery_method' => $listing->delivery_method,
            'quantity' => (int) $listing->quantity,
            'delivery_item_info' => $this->deliveryFlags($listing),
        ];
    }

    private function serializeDeliveryItem(DeliveryItem $item): array
    {
        return [
            'id' => $item->id,
            'data' => $item->data,
            'is_used' => (bool) $item->is_used,
            'order_number' => $item?->order ? $item?->order?->order_number : null,
        ];
    }

    private function normalizeDeliveryItems(array $deliveryItems): array
    {
        $normalized = [];

        foreach ($deliveryItems as $item) {
            if (is_array($item)) {
                $data = $item['data'] ?? null;
                $id = $item['id'] ?? null;
            } else {
                $data = $item;
                $id = null;
            }

            if (!is_string($data) || trim($data) === '') {
                continue;
            }

            $normalized[] = [
                'id' => $id ? (int) $id : null,
                'data' => $data,
            ];
        }

        return $normalized;
    }

    private function isDigitalListing(Listing $listing): bool
    {
        $type = $listing->type;
        $typeValue = $type instanceof BackedEnum ? $type->value : (string) $type;

        return $typeValue === 'digital';
    }

    private function countReadyDeliveryItems(Listing $listing): int
    {
        return isset($listing->ready_delivery_items_count)
            ? (int) $listing->ready_delivery_items_count
            : $listing->deliveryItems()->whereNull('order_id')->whereNotNull('data')->where('is_used', 0)->count();
    }

    private function attributeGroups(Request $request): array
    {
        $groups = $request->input('attribute_groups', []);
        if (is_string($groups)) {
            $decoded = json_decode($groups, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($groups) ? $groups : [];
    }

    private function syncAttributes(Listing $listing, array $attributeGroups): void
    {
        $existingIds = $listing->listingAttributes()->pluck('id')->toArray();
        $keepIds = [];

        foreach ($attributeGroups as $group) {
            $groupName = $group['group_name'] ?? null;
            if (!$groupName || empty($group['attributes']) || !is_array($group['attributes'])) {
                continue;
            }

            foreach ($group['attributes'] as $attr) {
                $data = [
                    'group' => $groupName,
                    'label' => $attr['label'],
                    'price' => $attr['price'],
                    'discount_type' => $attr['discount_type'] ?? 'amount',
                    'discount_amount' => (float) ($attr['discount_amount'] ?? 0),
                    'qty' => $attr['qty'] ?? 0,
                ];

                if (!empty($attr['id']) && in_array($attr['id'], $existingIds)) {
                    $listing->listingAttributes()->where('id', $attr['id'])->update($data);
                    $keepIds[] = (int) $attr['id'];
                } else {
                    $newAttr = $listing->listingAttributes()->create($data);
                    $keepIds[] = $newAttr->id;
                }
            }
        }

        $listing->listingAttributes()->whereNotIn('id', $keepIds)->delete();
    }

    private function updateListingFromAttributes(Listing $listing): void
    {
        $listing->load('listingAttributes');
        if ($listing->listingAttributes->isNotEmpty()) {
            $listing->update([
                'quantity' => $listing->listingAttributes->sum('qty'),
            ]);
        }
    }
}
