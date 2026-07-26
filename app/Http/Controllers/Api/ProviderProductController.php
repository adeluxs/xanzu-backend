<?php

namespace App\Http\Controllers\Api;

use App\Enums\ListingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ListingResource;
use App\Http\Resources\OrderResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingAttribute;
use App\Models\ListingGallery;
use App\Models\Order;
use App\Models\Provider;
use App\Services\ProviderProducts\ProviderProductGatewayResolver;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class ProviderProductController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ProviderProductGatewayResolver $gatewayResolver)
    {
    }

    public function config()
    {
        $categories = CategoryResource::withChildren(Category::active()
            ->trending()
            ->isCategory()
            ->orderBy('order')
            ->get(['id', 'name']));
        $brands = BrandResource::collection(Brand::active()->get(['id', 'name']));

        return $this->successResponse([
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }

    public function products(Request $request): JsonResponse
    {

        $user = $request->user();

        $products = Listing::where('provider_id', $user->provider->id)->where('seller_id', $user->id)->when($request->filled('search'), function ($query) use ($request) {
            $query->search($request->input('search'));
        })->whereNotNull('provider_product_id')->when($request->filled('category_id'), function ($query) use ($request) {
            $query->where('category_id', $request->input('category_id'));
        })->paginate($request->input('per_page', 20));

        return $this->successResponse(data: [
            'products' => ListingResource::frontend($products),
        ], meta: [
            'current_page' => $products->currentPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
        ]);
    }

    // product delete
    public function delete(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $product = Listing::where('provider_id', $user->provider->id)->where('seller_id', $user->id)->where('id', $id)->first();

        if (!$product) {
            return $this->errorResponse('Product not found.', 404);
        }

        $product->delete();

        return $this->successResponse(message: 'Product deleted successfully.');
    }

    // providers orders
    public function orders(Request $request): JsonResponse
    {
        $user = $request->user();

        $orders = Order::whereRelation('items', function ($query) use ($user, $request) {
            $query->where(function ($query) use ($user) {
                $query->where('provider_id', $user->provider->id)->orWhere('seller_id', $user->id);
            })->when($request->filled('search'), function ($query) use ($request) {
                $query->whereLike('product_name', '%' . $request->input('search') . '%');
            });
        })->where('is_bnpl', true)->whereDoesntHave('listing')->latest()->paginate($request->input('per_page', 20));
        return $this->successResponse(data: [
            'orders' => OrderResource::frontend($orders),
        ], meta: [
            'current_page' => $orders->currentPage(),
            'per_page' => $orders->perPage(),
            'total' => $orders->total(),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider_id' => ['nullable', 'exists:providers,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $provider = $this->resolveProvider($request);

        if (!$provider) {
            return $this->errorResponse('Provider not found or inactive.', 404);
        }

        try {
            $gateway = $this->gatewayResolver->resolve($provider);

            $products = cache()->remember(
                key: "provider:{$provider->id}:products:search:{$request->input('search', '')}:page:{$request->input('page', 1)}:per_page:{$request->input('per_page', 20)}",
                ttl: now()->addMinutes(10),
                callback: function () use ($gateway, $provider, $request) {
                    return $gateway->searchProducts(
                        provider: $provider,
                        search: $request->input('search'),
                        page: (int) $request->input('page', 1),
                        perPage: (int) $request->input('per_page', 20),
                    );
                }
            );

            return $this->successResponse(
                data: ['products' => $products],
                message: 'Provider products fetched successfully.',
                meta: [
                    'current_page' => (int) $request->input('page', 1),
                    'per_page' => (int) $request->input('per_page', 20),

                ]
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 502);
        }
    }

    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider_id' => ['nullable', 'exists:providers,id'],
            'remote_product_id' => ['nullable', 'max:191'],
            'remote_product_ids' => ['nullable', 'array', 'min:1'],
            'remote_product_ids.*' => ['required', 'max:191'],
            'category_id' => ['nullable'],
            'category_id.*' => ['nullable', 'exists:categories,id'],
            'subcategory_id' => ['nullable'],
            'subcategory_id.*' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable'],
            'brand_id.*' => ['nullable', 'exists:brands,id'],
            'status' => ['nullable', 'in:' . implode(',', array_column(ListingStatus::cases(), 'value'))],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        if (!$request->filled('remote_product_id') && !$request->filled('remote_product_ids')) {
            return $this->errorResponse('Please provide remote_product_id or remote_product_ids.', 422);
        }

        if ($request->filled('remote_product_ids')) {
            $remoteProductIds = (array) $request->input('remote_product_ids', []);

            foreach (['category_id', 'subcategory_id', 'brand_id'] as $field) {
                $value = $request->input($field);

                if (is_array($value) && count($value) !== count($remoteProductIds)) {
                    return $this->errorResponse("The {$field} array must have the same length as remote_product_ids.", 422);
                }
            }
        }

        if (!$this->isValidSingleOrArrayId($request->input('category_id'), 'categories')) {
            return $this->errorResponse('The selected category_id is invalid.', 422);
        }

        if (!$this->isValidSingleOrArrayId($request->input('subcategory_id'), 'categories')) {
            return $this->errorResponse('The selected subcategory_id is invalid.', 422);
        }

        if (!$this->isValidSingleOrArrayId($request->input('brand_id'), 'brands')) {
            return $this->errorResponse('The selected brand_id is invalid.', 422);
        }

        $provider = $this->resolveProvider($request);

        if (!$provider) {
            return $this->errorResponse('Provider not found or inactive.', 404);
        }

        try {
            $gateway = $this->gatewayResolver->resolve($provider);
            $remoteProductIds = $request->filled('remote_product_ids')
                ? array_values((array) $request->input('remote_product_ids', []))
                : [$request->input('remote_product_id')];

            $defaultCategoryId = Category::active()->isCategory()->value('id');

            if (!$defaultCategoryId && !$request->filled('category_id')) {
                return $this->errorResponse('Please provide category_id because no active parent category exists.', 422);
            }

            $insertedListings = [];
            $updatedListings = [];

            foreach ($remoteProductIds as $index => $remoteProductId) {
                $categoryId = $this->resolveIndexedId($request->input('category_id'), $index) ?: $defaultCategoryId;
                $subcategoryId = $this->resolveIndexedId($request->input('subcategory_id'), $index);
                $brandId = $this->resolveIndexedId($request->input('brand_id'), $index);

                if (!$categoryId) {
                    return $this->errorResponse('Please provide category_id for each product because no active parent category exists.', 422);
                }

                $product = $gateway->fetchProductById($provider, (string) $remoteProductId);

                $existingListing = Listing::query()
                    ->where('provider_id', $provider->id)
                    ->where('provider_product_id', $product['provider_product_id'])
                    ->first();

                if ($existingListing) {
                    $updatedListings[] = DB::transaction(function () use ($request, $existingListing, $product, $categoryId, $subcategoryId, $brandId) {
                        return $this->syncImportedListing($existingListing, $request, $product, $categoryId, $subcategoryId, $brandId);
                    });
                    continue;
                }

                $insertedListings[] = DB::transaction(function () use ($request, $provider, $product, $categoryId, $subcategoryId, $brandId) {
                    return $this->syncImportedListing(null, $request, $product, $categoryId, $subcategoryId, $brandId, $provider->id);
                });
            }

            $hasSingleRequest = count($remoteProductIds) === 1;

            if ($hasSingleRequest && !empty($updatedListings) && empty($insertedListings)) {
                return $this->successResponse(
                    data: [
                        'updated' => true,
                        'listing' => ListingResource::collection($updatedListings),
                    ],
                    message: 'Product updated successfully.',
                );
            }

            if ($hasSingleRequest && !empty($insertedListings)) {
                return $this->successResponse(
                    data: [
                        'already_exists' => false,
                        'listing' => ListingResource::collection($insertedListings),
                    ],
                    message: 'Product imported successfully.',
                    code: 201,
                );
            }

            return $this->successResponse(
                data: [
                    'inserted_count' => count($insertedListings),
                    'updated_count' => count($updatedListings),
                    'inserted_listings' => ListingResource::collection(($insertedListings)),
                    'updated_listings' => ListingResource::collection(($updatedListings)),
                ],
                message: 'Products import completed.',
                code: 201,
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 502);
        }
    }

    private function resolveProvider(Request $request): ?Provider
    {
        if ($request->filled('provider_id')) {
            return Provider::active()->find($request->integer('provider_id'));
        }

        return Provider::active()
            ->where('user_id', $request->user()->id)
            ->orderBy('id')
            ->first();
    }

    private function resolveIndexedId(mixed $value, int $index): ?int
    {
        if (is_array($value)) {
            $value = $value[$index] ?? null;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function isValidSingleOrArrayId(mixed $value, string $table): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_array($value)) {
            return true;
        }

        return DB::table($table)->where('id', (int) $value)->exists();
    }

    private function syncImportedListing(?Listing $listing, Request $request, array $product, int $categoryId, ?int $subcategoryId, ?int $brandId, ?int $providerId = null): Listing
    {
        $productType = data_get($product, 'source_payload.virtual', false) || data_get($product, 'source_payload.downloadable', false)
            ? 'digital'
            : 'physical';

        $attributes = (array) ($product['attributes'] ?? []);
        $galleryImages = (array) ($product['gallery_images'] ?? []);

        if (!$listing) {
            $listing = new Listing();
            $listing->seller_id = auth()->id();
            $listing->slug = $this->generateUniqueSlug($product['slug'] ?: $product['product_name']);
        }

        $listing->fill([
            'category_id' => $categoryId,
            'subcategory_id' => $subcategoryId,
            'brand_id' => $brandId,
            'provider_id' => $providerId ?? $listing->provider_id,
            'provider_product_id' => $product['provider_product_id'],
            'product_url' => $product['product_url'],
            'type' => $productType,
            'product_name' => $product['product_name'],
            'description' => $product['description'] ?: $product['product_name'],
            'price' => max(0, (float) $product['price']),
            'discount_type' => $product['discount_type'] ?? 'none',
            'discount_value' => max(0, (float) ($product['discount_value'] ?? 0)),
            'quantity' => max(0, (int) $product['quantity']),
            'thumbnail' => $product['thumbnail'],
            'delivery_method' => 'manual',
            'delivery_speed' => '0',
            'delivery_speed_unit' => 'hour',
            'status' => $request->input('status', $product['listing_status'] ?? ListingStatus::Draft->value),
            'is_approved' => 1,
            'is_flash' => 0,
            'has_attributes' => !empty($attributes),
            'avg_rating' => max(0, (float) ($product['avg_rating'] ?? 0)),
        ]);

        $listing->save();

        $listing->images()->delete();
        $listing->listingAttributes()->delete();

        foreach ($galleryImages as $imagePath) {
            ListingGallery::create([
                'listing_id' => $listing->id,
                'image_path' => $imagePath,
            ]);
        }

        foreach ($attributes as $attribute) {
            ListingAttribute::create([
                'listing_id' => $listing->id,
                'group' => (string) data_get($attribute, 'group', 'Option'),
                'label' => (string) data_get($attribute, 'label', 'N/A'),
                'price' => max(0, (float) data_get($attribute, 'price', 0)),
                'discount_type' => data_get($attribute, 'discount_type'),
                'discount_amount' => max(0, (float) data_get($attribute, 'discount_amount', 0)),
                'qty' => max(0, (int) data_get($attribute, 'qty', 0)),
            ]);
        }

        $listing->update([
            'sold_count' => max(0, (int) ($product['sold_count'] ?? 0)),
            'quantity' => $listing->has_attributes ? $listing->listingAttributes()->sum('qty') : max(0, (int) $product['quantity']),
        ]);

        return $listing->fresh(['listingAttributes', 'images']);
    }

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug ?: 'provider-product';

        if (!Listing::where('slug', $slug)->exists()) {
            return $slug;
        }

        return $slug . '-' . ((int) Listing::max('id') + 1);
    }
}
