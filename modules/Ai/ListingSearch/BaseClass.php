<?php

namespace Modules\Ai\ListingSearch;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Provider;
use Modules\Ai\ListingSearch\Provider\Gemini;
use Modules\Ai\ListingSearch\Provider\Groq;
use Modules\Ai\ListingSearch\Provider\Ollama;
use Modules\Ai\ListingSearch\Provider\OpenAI;
use Modules\Ai\ListingSearch\Traits\HasEnvironmentAwareErrors;

class BaseClass
{
    use HasEnvironmentAwareErrors;

    public function parse(string $userCommand): array
    {
        $provider = strtolower(setting('ai_provider', 'system'));
        $providerClass = $this->getProvider($provider);

        if (!$providerClass) {
            throw new \Exception($this->exceptionMessage('Provider not found.', 'Provider not available.'));
        }

        $contact = new $providerClass();
        $response = $contact->process($this->preparePrompt($userCommand));

        return $this->normalizeResponse($response);
    }

    protected function getProvider(string $provider): ?string
    {
        return [
            'ollama' => Ollama::class,
            'groq' => Groq::class,
            'openai' => OpenAI::class,
            'gemini' => Gemini::class,
        ][$provider] ?? null;
    }

    protected function preparePrompt(string $userCommand): string
    {
        $template = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'prompt.txt');

        $categories = Category::active()
            ->select('id', 'name', 'parent_id')
            ->orderBy('name')
            ->get()
            ->map(fn($item) => $item->id . ':' . trim((string) $item->name) . ',' . ($item->parent_id ?? 'null'))
            ->implode(PHP_EOL);

        $brands = Brand::where('status', 1)
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn($item) => $item->id . ':' . trim((string) $item->name))
            ->implode(PHP_EOL);

        $providers = Provider::where('status', 1)
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn($item) => $item->id . ':' . trim((string) $item->name))
            ->implode(PHP_EOL);

        return str_replace(
            ['{{USER_INPUT}}', '{{CATEGORIES}}', '{{BRANDS}}', '{{PROVIDERS}}'],
            [trim($userCommand), $categories, $brands, $providers],
            $template
        );
    }

    protected function normalizeResponse(mixed $response): array
    {
        $payload = is_object($response) ? (array) $response : (array) $response;

        $defaults = [
            'search' => null,
            'category_id' => null,
            'subcategory_id' => null,
            'brand_id' => null,
            'provider_id' => null,
            'min_price' => null,
            'max_price' => null,
            'rating' => null,
            'type' => null,
            'sort_by' => null,
            'sort_dir' => null,
        ];

        $filters = array_intersect_key($payload, $defaults) + $defaults;

        foreach (['category_id', 'subcategory_id', 'brand_id', 'provider_id'] as $idKey) {
            if ($filters[$idKey] !== null && is_numeric($filters[$idKey])) {
                $filters[$idKey] = (int) $filters[$idKey];
            } else {
                $filters[$idKey] = null;
            }
        }

        foreach (['min_price', 'max_price', 'rating'] as $numericKey) {
            if ($filters[$numericKey] !== null && is_numeric($filters[$numericKey])) {
                $filters[$numericKey] = (float) $filters[$numericKey];
            } else {
                $filters[$numericKey] = null;
            }
        }

        $filters['search'] = is_string($filters['search']) ? trim($filters['search']) : null;
        if ($filters['search'] === '') {
            $filters['search'] = null;
        }

        $allowedTypes = ['latest', 'popular', 'discounted'];
        $filters['type'] = in_array($filters['type'], $allowedTypes, true) ? $filters['type'] : null;

        $allowedSortBy = ['price', 'sold_count', 'avg_rating', 'created_at'];
        $filters['sort_by'] = in_array($filters['sort_by'], $allowedSortBy, true) ? $filters['sort_by'] : null;

        $filters['sort_dir'] = $filters['sort_dir'] === 'asc' ? 'asc' : ($filters['sort_dir'] === 'desc' ? 'desc' : null);

        if ($filters['rating'] !== null) {
            $filters['rating'] = max(0.0, min(5.0, $filters['rating']));
        }

        if ($filters['min_price'] !== null && $filters['max_price'] !== null && $filters['min_price'] > $filters['max_price']) {
            [$filters['min_price'], $filters['max_price']] = [$filters['max_price'], $filters['min_price']];
        }

        return array_filter($filters, static fn($value) => $value !== null);
    }
}
