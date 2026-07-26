<?php

namespace App\Services\ProviderProducts\Contracts;

use App\Models\Provider;

interface ProviderProductGateway
{
    /**
     * Search remote provider products.
     */
    public function searchProducts(Provider $provider, ?string $search = null, int $page = 1, int $perPage = 20): array;

    /**
     * Fetch one remote product and normalize it to local import schema.
     */
    public function fetchProductById(Provider $provider, string|int $remoteProductId): array;
}
