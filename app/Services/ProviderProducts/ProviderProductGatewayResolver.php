<?php

namespace App\Services\ProviderProducts;

use App\Enums\ProviderPlatform;
use App\Models\Provider;
use App\Services\ProviderProducts\Contracts\ProviderProductGateway;
use App\Services\WooCommerceProductService;
use InvalidArgumentException;

class ProviderProductGatewayResolver
{
    public function __construct(private readonly WooCommerceProductService $wooCommerceProductService)
    {
    }

    public function resolve(Provider $provider): ProviderProductGateway
    {
        return match ($provider->platform) {
            ProviderPlatform::WORDPRESS_WOOCOMMERCE->value => $this->wooCommerceProductService,
            default => throw new InvalidArgumentException('Provider platform is not supported yet.'),
        };
    }
}
