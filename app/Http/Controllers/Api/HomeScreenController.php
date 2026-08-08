<?php

namespace App\Http\Controllers\Api;

use App\Enums\ListingReview;
use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CouponResource;
use App\Http\Resources\ListingResource;
use App\Http\Resources\ListingReviewResource;
use App\Http\Resources\ProviderResource;
use App\Jobs\GenerateListingReviewSummary;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Listing;
use App\Models\ListingReview as ListingReviewModel;
use App\Models\Provider;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Modules\Ai\ListingSearch\BaseClass as AiListingSearch;

class HomeScreenController extends Controller
{
    use ApiResponse;

    /**
     * App home screen data:
     *  - Banners
     *  - Home coupon (single)
     *  - Trending categories (parent only, not sub-categories)
     *  - Popular brands
     *  - Flash-sale products
     *  - Latest active products
     *  - Popular products (by sold_count)
     *  - Discounted products (item-level or attribute-level discount)
     */
    public function index(): JsonResponse
    {
        // Home data is identical for every visitor for a short window. Cache
        // the Eloquent collections (not the final HTTP response) so Resource
        // serialization remains request-aware while eliminating the repeated
        // query burst that made the mobile home screen feel slow.
        $payload = Cache::remember('api.home-screen.v3', now()->addSeconds(45), function (): array {
            $productEagerLoad = [
                'category:id,name,slug',
                'brand:id,name,slug,image',
                'listingAttributes',
            ];

            return [
                'coupon' => Coupon::where('status', 1)
                    ->where('expires_at', '>=', now())
                    ->where('is_home', 1)
                    ->first(),
                'banners' => Banner::query()
                    ->with('category:id,name,slug,image,description')
                    ->latest()
                    ->get(),
                'trending_categories' => Category::active()
                    ->trending()
                    ->isCategory()
                    ->orderBy('order')
                    ->get(),
                'popular_brands' => Brand::where('status', 1)->isPopular()->get(),
                'flash_sale_products' => Listing::where('status', 'active')
                    ->where('is_approved', 1)
                    ->where('is_flash', true)
                    ->with($productEagerLoad)
                    ->latest()
                    ->take(6)
                    ->get(),
                'best_selling_products' => Listing::where('status', 'active')
                    ->where('is_approved', 1)
                    ->orderByDesc('sold_count')
                    ->orderByDesc('avg_rating')
                    ->with($productEagerLoad)
                    ->take(6)
                    ->get(),
                'trending_products' => Listing::where('status', 'active')
                    ->where('is_approved', 1)
                    ->orderByDesc('is_trending')
                    ->latest()
                    ->with($productEagerLoad)
                    ->take(6)
                    ->get(),
                'latest_products' => Listing::where('status', 'active')
                    ->where('is_approved', 1)
                    ->with($productEagerLoad)
                    ->latest()
                    ->take(6)
                    ->get(),
                'providers' => Provider::where('status', 1)->latest()->take(20)->get(),
                'flash_sale_meta' => [
                    'flash_sale_status' => setting('flash_sale_status'),
                    'flash_sale_start_date' => setting('flash_sale_start_date'),
                    'flash_sale_end_date' => setting('flash_sale_end_date'),
                ],
            ];
        });

        return $this->successResponse(
            data: [
                'banners' => BannerResource::collection($payload['banners']),
                'coupon' => $payload['coupon'] ? new CouponResource($payload['coupon']) : null,
                'trending_categories' => CategoryResource::collection($payload['trending_categories']),
                'popular_brands' => BrandResource::collection($payload['popular_brands']),
                'products' => [
                    'flash_sale_products' => ListingResource::withDetails($payload['flash_sale_products']),
                    'trending_products' => ListingResource::withDetails($payload['trending_products']),
                    'best_selling_products' => ListingResource::withDetails($payload['best_selling_products']),
                    'latest_products' => ListingResource::withDetails($payload['latest_products']),
                ],
                'providers' => ProviderResource::collection($payload['providers']),
                'flash_sale_meta' => $payload['flash_sale_meta'],
            ],
            message: 'Home screen data fetched successfully',
        );
    }

    /**
     * Extract pagination meta from a paginator instance.
     */
    private function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    private function resolveAiListingFilters(Request $request): array
    {
        if (!$request->filled('ai_search')) {
            return [];
        }

        try {
            return (new AiListingSearch())->parse((string) $request->input('ai_search'));
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    private function resolveFilterValue(Request $request, array $aiFilters, string $key): mixed
    {
        if ($request->filled($key)) {
            return $request->input($key);
        }

        return $aiFilters[$key] ?? null;
    }

    private function reviewSummaryCacheKey(int $listingId): string
    {
        return 'listing_review_summary:' . $listingId;
    }

    public function getCachedReviewSummary(int $listingId): ?string
    {
        $cacheKey = $this->reviewSummaryCacheKey($listingId);
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached === '' ? null : (string) $cached;
        }

        // AI is intentionally kept off the product-details request path. A
        // database/Redis queue worker can refresh the summary independently.
        // If the application is configured with the synchronous queue driver,
        // skip generation here rather than making the mobile page wait on an
        // external model provider.
        if (config('queue.default') !== 'sync') {
            $queuedKey = $cacheKey . ':queued';
            if (Cache::add($queuedKey, true, now()->addMinutes(2))) {
                GenerateListingReviewSummary::dispatch($listingId);
            }
        }

        return null;
    }

    public function productSections(): JsonResponse
    {
        $payload = Cache::remember('api.product-sections.v1', now()->addSeconds(45), function (): array {
            $eager = [
                'category:id,name,slug',
                'brand:id,name,slug,image',
                'listingAttributes',
            ];

            $base = static fn () => Listing::query()
                ->active()
                ->where('is_approved', 1)
                ->with($eager);

            return [
                'latest_products' => $base()->latest()->limit(6)->get(),
                'popular_products' => $base()
                    ->where('sold_count', '>', 0)
                    ->orderByDesc('sold_count')
                    ->latest('id')
                    ->limit(6)
                    ->get(),
                'discounted_products' => $base()
                    ->where(function ($query) {
                        $query->where(function ($q) {
                            $q->where('has_attributes', true)
                                ->whereHas('listingAttributes', fn ($attr) => $attr->where('discount_amount', '>', 0));
                        })->orWhere(function ($q) {
                            $q->where('has_attributes', false)
                                ->where('discount_type', '!=', 'none')
                                ->where('discount_value', '>', 0);
                        });
                    })
                    ->latest()
                    ->limit(6)
                    ->get(),
            ];
        });

        return $this->successResponse(
            data: [
                'latest_products' => ListingResource::withDetails($payload['latest_products']),
                'popular_products' => ListingResource::withDetails($payload['popular_products']),
                'discounted_products' => ListingResource::withDetails($payload['discounted_products']),
            ],
            message: 'Product sections fetched successfully',
        );
    }

    public function listingFilter(Request $request): JsonResponse
    {
        $perPage = max(1, (int) $request->input('per_page', 20));
        $aiFilters = $this->resolveAiListingFilters($request);


        $categoryId = $this->resolveFilterValue($request, $aiFilters, 'category_id');
        $subcategoryId = $this->resolveFilterValue($request, $aiFilters, 'subcategory_id');
        $brandId = $this->resolveFilterValue($request, $aiFilters, 'brand_id');
        $search = $this->resolveFilterValue($request, $aiFilters, 'search');
        $minPrice = $this->resolveFilterValue($request, $aiFilters, 'min_price');
        $maxPrice = $this->resolveFilterValue($request, $aiFilters, 'max_price');
        $rating = $this->resolveFilterValue($request, $aiFilters, 'rating');
        $providerId = $this->resolveFilterValue($request, $aiFilters, 'provider_id');
        $type = $this->resolveFilterValue($request, $aiFilters, 'type');
        $sortByInput = $this->resolveFilterValue($request, $aiFilters, 'sort_by');
        $sortDirInput = $this->resolveFilterValue($request, $aiFilters, 'sort_dir');

        $listings = Listing::query()
            ->when($categoryId !== null, function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            })
            ->when($subcategoryId !== null, function ($q) use ($subcategoryId) {
                $q->where('subcategory_id', $subcategoryId);
            })
            ->when($brandId !== null, function ($q) use ($brandId) {
                $q->where('brand_id', $brandId);
            })
            ->when($search !== null, function ($q) use ($search) {
                $q->where('product_name', 'like', '%' . $search . '%');
            })
            ->when($minPrice !== null, function ($q) use ($minPrice) {
                $q->where('price', '>=', $minPrice);
            })
            ->when($maxPrice !== null, function ($q) use ($maxPrice) {
                $q->where('price', '<=', $maxPrice);
            })
            ->when($rating !== null, function ($q) use ($rating) {
                $q->where('avg_rating', '>=', $rating);
            })
            // provider
            ->when($providerId !== null, function ($q) use ($providerId) {
                $q->where('seller_id', $providerId);
            })
            // additional types: flash | best_selling | trending
            ->when($type === 'flash', function ($q) {
                $q->where('is_flash', true);
            })
            ->when($type === 'best_selling', function ($q) {
                $q->orderByDesc('sold_count')->orderByDesc('avg_rating');
            })
            ->when($type === 'trending', function ($q) {
                $q->orderByDesc('is_trending');
            })
            // type: latest | popular | discounted
            ->when($type === 'popular', function ($q) {
                $q->where('sold_count', '>', 0)->orderByDesc('sold_count');
            })
            ->when($type === 'latest', function ($q) {
                $q->latest();
            })
            ->when($type === 'discounted', function ($q) {
                $q->where(function ($q2) {
                    $q2->where(function ($q3) {
                        $q3->where('has_attributes', true)
                            ->whereHas('listingAttributes', function ($q4) {
                                $q4->where('discount_amount', '>', 0);
                            });
                    })
                        ->orWhere(function ($q3) {
                            $q3->where('has_attributes', false)
                                ->where('discount_type', '!=', 'none')
                                ->where('discount_value', '>', 0);
                        });
                });
            })
            ->when($sortByInput !== null, function ($q) use ($sortByInput, $sortDirInput) {
                $allowed = ['price', 'sold_count', 'avg_rating', 'created_at'];
                $sortBy = in_array($sortByInput, $allowed, true) ? $sortByInput : 'created_at';
                $sortDir = $sortDirInput === 'asc' ? 'asc' : 'desc';
                $q->orderBy($sortBy, $sortDir);
            }, function ($q) {
                $q->latest();
            })
            ->active()
            ->where('is_approved', 1)
            ->with([
                'category:id,name,slug',
                'brand:id,name,slug,image',
                'listingAttributes',
            ])
            ->paginate($perPage);

        return $this->successResponse(
            data: [
                'listings' => ListingResource::withDetails($listings),
                'categories' => $request->has('with_categories') ? CategoryResource::collection(Category::active()->isCategory()->orderBy('order')->get()) : null,
                'ai_filters' => $request->filled('ai_search') ? $aiFilters : null,
            ],
            message: 'Listings fetched successfully',
            meta: $this->paginationMeta($listings),
        );
    }

    public function filterData(Request $request): JsonResponse
    {
        $aiFilters = $this->resolveAiListingFilters($request);

        $categoryId = $this->resolveFilterValue($request, $aiFilters, 'category_id');
        $subcategoryId = $this->resolveFilterValue($request, $aiFilters, 'subcategory_id');
        $brandId = $this->resolveFilterValue($request, $aiFilters, 'brand_id');
        $search = $this->resolveFilterValue($request, $aiFilters, 'search');
        $minPrice = $this->resolveFilterValue($request, $aiFilters, 'min_price');
        $maxPrice = $this->resolveFilterValue($request, $aiFilters, 'max_price');
        $rating = $this->resolveFilterValue($request, $aiFilters, 'rating');
        $providerId = $this->resolveFilterValue($request, $aiFilters, 'provider_id');

        $includeTaxonomy = ! $request->has('with_taxonomy') || $request->boolean('with_taxonomy');
        $taxonomy = $includeTaxonomy
            ? Cache::remember('api.catalog.filter-taxonomy.v1', now()->addMinutes(3), static function (): array {
                return [
                    'categories' => Category::active()->isCategory()->orderBy('order')->get(),
                    'brands' => Brand::where('status', 1)->orderBy('name')->get(),
                    'providers' => Provider::where('status', 1)->latest()->take(20)->get(),
                ];
            })
            : ['categories' => collect(), 'brands' => collect(), 'providers' => collect()];
        $categories = $taxonomy['categories'];
        $brands = $taxonomy['brands'];
        $providers = $taxonomy['providers'];

        $priceRange = Listing::active()->where('is_approved', 1)
            ->when($categoryId !== null, function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            })->when($subcategoryId !== null, function ($q) use ($subcategoryId) {
                $q->where('subcategory_id', $subcategoryId);
            })->when($brandId !== null, function ($q) use ($brandId) {
                $q->where('brand_id', $brandId);
            })->when($providerId !== null, function ($q) use ($providerId) {
                $q->where('seller_id', $providerId);
            })->when($search !== null, function ($q) use ($search) {
                $q->where('product_name', 'like', '%' . $search . '%');
            })->when($minPrice !== null, function ($q) use ($minPrice) {
                $q->where('price', '>=', $minPrice);
            })->when($maxPrice !== null, function ($q) use ($maxPrice) {
                $q->where('price', '<=', $maxPrice);
            })->when($rating !== null, function ($q) use ($rating) {
                $q->where('avg_rating', '>=', $rating);
            })
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')->first();

        return $this->successResponse(
            data: [
                'categories' => $includeTaxonomy ? CategoryResource::withChildren($categories, true) : null,
                'brands' => $includeTaxonomy ? BrandResource::collection($brands) : null,
                'providers' => $includeTaxonomy ? ProviderResource::collection($providers) : null,
                'price_range' => [
                    'min_price' => $priceRange->min_price ?? '0.00',
                    'max_price' => $priceRange->max_price ?? '0.00',
                ],
            ],
            message: 'Filter data fetched successfully',
        );
    }

    public function productDetails(Request $request, $id): JsonResponse
    {
        $listing = Listing::where('id', $id)
            ->active()
            ->where('is_approved', 1)
            ->with([
                'category:id,name,slug',
                'brand:id,name,slug,image',
                'provider:id,name,slug,image',
                'reviews' => function ($q) {
                    $q->whereNull('parent_id')
                        ->where('status', 'approved')
                        ->with([
                            'buyer:id,username,first_name,last_name,avatar',
                            'reply' => function ($replyQuery) {
                                $replyQuery->where('status', 'approved')
                                    ->with('buyer:id,username,first_name,last_name,avatar');
                            },
                        ])
                        ->latest()
                        ->limit(5);
                },
                'images:id,listing_id,image_path',
                'listingAttributes' => function ($query) {
                    $query
                        ->select('id', 'group', 'label', 'listing_id', 'qty', 'price', 'discount_type', 'final_price', 'discount_amount');
                },
            ])
            ->first();

        if (!$listing) {
            return $this->errorResponse(
                'Product not found',
            );
        }
        $request->merge(['fullData' => true]); // To indicate we want full details in the resource

        return $this->successResponse(
            data: new ListingResource($listing),
            message: 'Product details fetched successfully',
        );
    }

    public function productReviews(Request $request, $id): JsonResponse
    {
        $perPage = max(1, (int) $request->input('per_page', 10));

        $listing = Listing::where('id', $id)
            ->active()
            ->where('is_approved', 1)
            ->first();

        if (!$listing) {
            return $this->errorResponse(
                'Product not found',
            );
        }

        $reviews = $listing->reviews()
            ->whereNull('parent_id')
            ->where('status', ListingReview::Approved)
            ->with([
                'buyer',
                'reply' => function ($replyQuery) {
                    $replyQuery->where('status', ListingReview::Approved)->with('buyer');
                },
            ])
            ->latest()
            ->paginate($perPage);

        $ratingGroups = $listing->approvedReviews()
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $reviewCountByRating = [
            '1' => (int) ($ratingGroups[1] ?? 0),
            '2' => (int) ($ratingGroups[2] ?? 0),
            '3' => (int) ($ratingGroups[3] ?? 0),
            '4' => (int) ($ratingGroups[4] ?? 0),
            '5' => (int) ($ratingGroups[5] ?? 0),
        ];
        $totalReviews = array_sum($reviewCountByRating);

        return $this->successResponse(
            data: [
                'listing_id' => $listing->id,
                'total_reviews' => $totalReviews,
                'review_count_by_rating' => $reviewCountByRating,
                'review_summary' => $this->getCachedReviewSummary((int) $listing->id),
                'reviews' => ListingReviewResource::collection($reviews),
                'can_review' => auth()->check() && $listing->canReview(auth()->id()),
            ],
            message: 'Product reviews fetched successfully',
            meta: $this->paginationMeta($reviews),
        );
    }

    /**
     * Get all trending categories ordered by order field
     */
    public function getTrendingCategories(): JsonResponse
    {
        $trendingCategories = Cache::remember('api.catalog.trending-categories.v1', now()->addMinutes(3), static fn() => Category::active()
            ->trending()
            ->isCategory()
            ->orderBy('order', 'asc')
            ->get());

        return $this->successResponse(
            data: CategoryResource::collection($trendingCategories),
            message: 'Trending categories fetched successfully',
        );
    }

    /**
     * Get all popular brands ordered by popularity
     */
    public function getPopularBrands(): JsonResponse
    {
        $popularBrands = Cache::remember('api.catalog.popular-brands.v1', now()->addMinutes(3), static fn() => Brand::where('status', 1)
            ->isPopular()
            ->orderBy('name', 'asc')
            ->get());

        return $this->successResponse(
            data: BrandResource::collection($popularBrands),
            message: 'Popular brands fetched successfully',
        );
    }

    /**
     * Get all active providers ordered by name
     */
    public function getPopularProviders(): JsonResponse
    {
        $popularProviders = Cache::remember('api.catalog.popular-providers.v1', now()->addMinutes(3), static fn() => Provider::where('status', 1)
            ->orderBy('name', 'asc')
            ->get());

        return $this->successResponse(
            data: ProviderResource::collection($popularProviders),
            message: 'Popular providers fetched successfully',
        );
    }

    public function couponByCode(Request $request, $code): JsonResponse
    {

        $validator = Validator::make(['code' => $code], [
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }


        $code = trim($code);

        // Default MySQL collations are case-insensitive, so a direct comparison
        // preserves normal coupon behavior while allowing an index on `code`.
        $coupon = Coupon::query()->where('code', $code)->first();

        if (!$coupon) {
            return $this->validationErrorResponse(__('Invalid coupon code.'));
        }

        if (!($coupon->status ?? 1)) {
            return $this->validationErrorResponse(__('Coupon is inactive.'));
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return $this->validationErrorResponse(__('Coupon has expired.'));
        }

        if ((int) $coupon->max_use_limit <= (int) $coupon->total_used) {
            return $this->validationErrorResponse(__('Coupon usage limit exceeded.'));
        }

        return $this->successResponse(
            data: new CouponResource($coupon),
            message: __('Coupon fetched successfully.')
        );
    }

    public function providerDetails(Request $request, $id): JsonResponse
    {
        $perPage = max(1, (int) $request->input('per_page', 20));

        $provider = Provider::active()->find($id);

        if (!$provider) {
            return $this->errorResponse(
                'Provider not found',
            );
        }

        $listings = Listing::query()
            ->where(function ($q) use ($provider) {
                $q->where('provider_id', $provider->id);

                if ($provider->user_id) {
                    $q->orWhere('seller_id', $provider->user_id);
                }
            })
            ->active()
            ->where('is_approved', 1)
            ->with([
                'category:id,name,slug',
                'brand:id,name,slug,image',
                'listingAttributes',
            ])
            ->latest()
            ->paginate($perPage);

        return $this->successResponse(
            data: [
                'provider' => new ProviderResource($provider),
                'listings' => ListingResource::withDetails($listings),
            ],
            message: 'Provider details fetched successfully',
            meta: $this->paginationMeta($listings),
        );
    }
}
