<?php

namespace App\Services;

use App\Enums\ProviderPlatform;
use App\Models\Provider;
use App\Services\ProviderProducts\Contracts\ProviderProductGateway;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class WooCommerceProductService implements ProviderProductGateway
{
    private const DEFAULT_UNMANAGED_STOCK_QTY = 9999;

    /**
     * Search WooCommerce products by keyword.
     */
    public function searchProducts(Provider $provider, ?string $search = null, int $page = 1, int $perPage = 20): array
    {
        $response = $this->http($provider)->get($this->productsEndpoint($provider), [
            'search' => $search,
            'page' => max(1, $page),
            'per_page' => min(100, max(1, $perPage)),
            'order' => 'desc',
            'orderby' => 'date',
        ]);

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessage($response->status()));
        }
        return collect($response->json())
            ->map(fn($item) => [
                'id' => (string) data_get($item, 'id'),
                'name' => (string) data_get($item, 'name', ''),
                'slug' => (string) data_get($item, 'slug', ''),
                'type' => (string) data_get($item, 'type', 'simple'),
                'price' => (float) (data_get($item, 'sale_price') ?: data_get($item, 'price') ?: data_get($item, 'regular_price') ?: 0),
                'regular_price' => (float) (data_get($item, 'regular_price') ?: data_get($item, 'price') ?: 0),
                'sale_price' => (float) (data_get($item, 'sale_price') ?: 0),
                'on_sale' => (bool) data_get($item, 'on_sale', false),
                'permalink' => data_get($item, 'permalink'),
                'image' => data_get($item, 'images.0.src'),
                'stock_quantity' => (int) (data_get($item, 'stock_quantity') ?: 0),
                'stock_status' => (string) data_get($item, 'stock_status', ''),
                'in_stock' => (bool) data_get($item, 'in_stock', (string) data_get($item, 'stock_status', '') === 'instock'),
                'total_sales' => (int) data_get($item, 'total_sales', 0),
            ])
            ->values()
            ->all();
    }

    /**
     * Fetch one WooCommerce product by ID and normalize it for listing import.
     */
    public function fetchProductById(Provider $provider, string|int $remoteProductId): array
    {
        $response = $this->http($provider)->get($this->productsEndpoint($provider) . '/' . $remoteProductId);

        if ($response->status() === 404) {
            throw new RuntimeException('WooCommerce product not found.');
        }

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessage($response->status()));
        }

        $product = $response->json();
        $isVariable = data_get($product, 'type') === 'variable';
        $variations = $isVariable ? $this->fetchVariations($provider, (string) data_get($product, 'id')) : [];

        $priceMap = $isVariable
            ? $this->variablePriceMapping($product, $variations)
            : $this->simplePriceMapping($product);

        $quantity = $this->resolveQuantity($product, $variations);
        $attributeRows = $isVariable
            ? $this->buildVariationAttributeRows($variations, $priceMap['base_final_price'], $quantity)
            : $this->buildSimpleAttributeRows($product, $quantity);

        $galleryImages = collect(data_get($product, 'images', []))
            ->map(fn($image) => data_get($image, 'src'))
            ->filter()
            ->values()
            ->all();

        return [
            'provider_product_id' => (string) data_get($product, 'id'),
            'product_name' => (string) data_get($product, 'name', ''),
            'slug' => (string) data_get($product, 'slug', ''),
            'type' => (string) data_get($product, 'type', 'simple'),
            'status' => (string) data_get($product, 'status', 'publish'),
            'listing_status' => $this->mapWooStatusToListingStatus((string) data_get($product, 'status', 'publish')),
            'description' => (string) (data_get($product, 'description') ?: data_get($product, 'short_description') ?: ''),
            'short_description' => (string) data_get($product, 'short_description', ''),
            'sku' => (string) data_get($product, 'sku', ''),
            'price' => $priceMap['price'],
            'discount_type' => $priceMap['discount_type'],
            'discount_value' => $priceMap['discount_value'],
            'quantity' => $quantity,
            'product_url' => data_get($product, 'permalink'),
            'thumbnail' => data_get($product, 'images.0.src'),
            'gallery_images' => array_slice($galleryImages, 1),
            'has_attributes' => !empty($attributeRows),
            'attributes' => $attributeRows,
            'sold_count' => (int) data_get($product, 'total_sales', 0),
            'avg_rating' => (float) data_get($product, 'average_rating', 0),
            'stock_status' => (string) data_get($product, 'stock_status', ''),
            'on_sale' => (bool) data_get($product, 'on_sale', false),
            'source_payload' => [
                'categories' => data_get($product, 'categories', []),
                'tags' => data_get($product, 'tags', []),
                'brands' => data_get($product, 'brands', []),
                'reviews_allowed' => (bool) data_get($product, 'reviews_allowed', false),
                'virtual' => (bool) data_get($product, 'virtual', false),
                'downloadable' => (bool) data_get($product, 'downloadable', false),
                'manage_stock' => (bool) data_get($product, 'manage_stock', false),
                'has_options' => (bool) data_get($product, 'has_options', false),
                'meta_data' => data_get($product, 'meta_data', []),
            ],
        ];
    }

    /**
     * Convert simple product regular/sale into listing base discount model.
     */
    private function simplePriceMapping(array $product): array
    {
        $current = $this->toMoney(data_get($product, 'price'));
        $regular = $this->toMoney(data_get($product, 'regular_price'));
        $sale = $this->toMoney(data_get($product, 'sale_price'));

        if ($regular <= 0) {
            $regular = $current > 0 ? $current : $sale;
        }

        $final = $sale > 0 ? $sale : ($current > 0 ? $current : $regular);

        if ($regular > $final && $final >= 0) {
            return [
                'base_final_price' => $final,
                'price' => $regular,
                'discount_type' => 'amount',
                'discount_value' => round($regular - $final, 2),
            ];
        }

        return [
            'base_final_price' => $final,
            'price' => $final,
            'discount_type' => 'none',
            'discount_value' => 0,
        ];
    }

    /**
     * For variable products, set listing base from cheapest variation's final price.
     */
    private function variablePriceMapping(array $product, array $variations): array
    {
        if (empty($variations)) {
            return $this->simplePriceMapping($product);
        }

        $normalized = collect($variations)->map(function (array $variation) {
            $current = $this->toMoney(data_get($variation, 'price'));
            $regular = $this->toMoney(data_get($variation, 'regular_price'));
            $sale = $this->toMoney(data_get($variation, 'sale_price'));

            if ($regular <= 0) {
                $regular = $current > 0 ? $current : $sale;
            }

            $final = $sale > 0 ? $sale : ($current > 0 ? $current : $regular);

            return [
                'final' => round(max(0, $final), 2),
                'regular' => round(max(0, $regular), 2),
            ];
        })->sortBy('final')->values();

        $base = $normalized->first();

        if (!$base) {
            return $this->simplePriceMapping($product);
        }

        if ($base['regular'] > $base['final']) {
            return [
                'base_final_price' => $base['final'],
                'price' => $base['regular'],
                'discount_type' => 'amount',
                'discount_value' => round($base['regular'] - $base['final'], 2),
            ];
        }

        return [
            'base_final_price' => $base['final'],
            'price' => $base['final'],
            'discount_type' => 'none',
            'discount_value' => 0,
        ];
    }

    /**
     * Build additive attribute rows for simple Woo attributes (no per-option price in Woo).
     */
    private function buildSimpleAttributeRows(array $product, int $quantity): array
    {
        return collect(data_get($product, 'attributes', []))
            ->flatMap(function (array $attribute) use ($quantity) {
                $groupName = (string) data_get($attribute, 'name', 'Option');
                $options = collect(data_get($attribute, 'options', []));

                return $options->map(function ($option) use ($groupName, $quantity) {
                    return [
                        'group' => $groupName,
                        'label' => (string) $option,
                        'price' => 0,
                        'discount_type' => null,
                        'discount_amount' => 0,
                        'qty' => $quantity,
                    ];
                });
            })
            ->values()
            ->all();
    }

    /**
     * Build additive rows from Woo variations using a single group to avoid combinational ambiguity.
     */
    private function buildVariationAttributeRows(array $variations, float $baseFinalPrice, int $defaultQty): array
    {
        return collect($variations)->map(function (array $variation) use ($baseFinalPrice, $defaultQty) {
            $current = $this->toMoney(data_get($variation, 'price'));
            $regular = $this->toMoney(data_get($variation, 'regular_price'));
            $sale = $this->toMoney(data_get($variation, 'sale_price'));

            if ($regular <= 0) {
                $regular = $current > 0 ? $current : $sale;
            }

            $final = $sale > 0 ? $sale : ($current > 0 ? $current : $regular);
            $finalAddon = round(max(0, $final - $baseFinalPrice), 2);
            $regularAddon = round(max(0, $regular - $baseFinalPrice), 2);
            $discountAmount = round(max(0, $regularAddon - $finalAddon), 2);

            $label = collect(data_get($variation, 'attributes', []))
                ->map(function ($attribute) {
                    $name = (string) (data_get($attribute, 'name') ?: data_get($attribute, 'slug', 'Option'));
                    $value = (string) (data_get($attribute, 'option') ?: 'N/A');

                    return $name . ': ' . $value;
                })
                ->filter()
                ->implode(' / ');

            return [
                'group' => 'Variation',
                'label' => $label ?: ('Variation #' . data_get($variation, 'id')),
                'price' => $regularAddon,
                'discount_type' => $discountAmount > 0 ? 'amount' : null,
                'discount_amount' => $discountAmount,
                'qty' => max(0, (int) (data_get($variation, 'stock_quantity') ?? $defaultQty)),
            ];
        })->values()->all();
    }

    private function resolveQuantity(array $product, array $variations = []): int
    {
        if (!empty($variations)) {
            $total = collect($variations)
                ->sum(fn($variation) => max(0, (int) (data_get($variation, 'stock_quantity') ?? 0)));

            if ($total > 0) {
                return $total;
            }

            $hasManagedFalseInStockVariation = collect($variations)->contains(function ($variation) {
                return !data_get($variation, 'manage_stock', false)
                    && data_get($variation, 'stock_status') === 'instock';
            });

            if ($hasManagedFalseInStockVariation) {
                return self::DEFAULT_UNMANAGED_STOCK_QTY;
            }
        }

        if (!data_get($product, 'manage_stock', false)) {
            return data_get($product, 'stock_status') === 'instock'
                ? self::DEFAULT_UNMANAGED_STOCK_QTY
                : 0;
        }

        $stockQuantity = data_get($product, 'stock_quantity');
        if ($stockQuantity !== null) {
            return max(0, (int) $stockQuantity);
        }

        return data_get($product, 'stock_status') === 'instock' ? 1 : 0;
    }

    private function mapWooStatusToListingStatus(string $wooStatus): string
    {
        return match ($wooStatus) {
            'publish' => 'active',
            'private', 'draft', 'pending' => 'draft',
            default => 'inactive',
        };
    }

    private function fetchVariations(Provider $provider, string $productId): array
    {
        $response = $this->http($provider)->get($this->productsEndpoint($provider) . '/' . $productId . '/variations', [
            'page' => 1,
            'per_page' => 100,
            'order' => 'asc',
            'orderby' => 'id',
        ]);

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessage($response->status()));
        }

        return collect($response->json())
            ->map(fn($variation) => is_array($variation) ? $variation : [])
            ->filter()
            ->values()
            ->all();
    }

    private function toMoney(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return round((float) $value, 2);
    }

    /**
     * Prepare authenticated HTTP client for WooCommerce API.
     *
     * @throws InvalidArgumentException
     */
    private function http(Provider $provider): PendingRequest
    {
        $this->guardProviderConfiguration($provider);

        $request = Http::timeout(20)
            ->acceptJson()
            ->asJson()
            ->withQueryParameters([
                'consumer_key' => $provider->api_key,
                'consumer_secret' => $provider->api_secret,
            ]);

        if ($this->shouldDisableSslVerification($provider)) {
            $request = $request->withoutVerifying();
        }

        return $request;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function guardProviderConfiguration(Provider $provider): void
    {
        if ($provider->platform !== ProviderPlatform::WORDPRESS_WOOCOMMERCE->value) {
            throw new InvalidArgumentException('Provider platform must be wordpress-woocommerce.');
        }

        if (blank($provider->platform_host) || blank($provider->api_key) || blank($provider->api_secret)) {
            throw new InvalidArgumentException('Provider WooCommerce credentials are incomplete.');
        }
    }

    private function productsEndpoint(Provider $provider): string
    {
        $baseUrl = trim((string) $provider->platform_host);

        if (!str_starts_with($baseUrl, 'http://') && !str_starts_with($baseUrl, 'https://')) {
            $baseUrl = 'https://' . $baseUrl;
        }

        return rtrim($baseUrl, '/') . '/wp-json/wc/v3/products';
    }

    private function shouldDisableSslVerification(Provider $provider): bool
    {
        $host = strtolower((string) $provider->platform_host);

        return app()->environment(['local', 'testing'])
            || str_contains($host, '.test')
            || str_contains($host, 'localhost')
            || str_contains($host, '127.0.0.1');
    }

    private function errorMessage(int $status): string
    {
        return match ($status) {
            401, 403 => 'WooCommerce credentials are invalid or unauthorized.',
            404 => 'WooCommerce resource not found.',
            default => 'Failed to communicate with WooCommerce.',
        };
    }
}
