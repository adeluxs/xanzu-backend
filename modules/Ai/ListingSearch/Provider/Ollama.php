<?php

namespace Modules\Ai\ListingSearch\Provider;

class Ollama extends \Modules\Ai\ListingSearch\Contact
{
    public function process(string $finalPrompt): mixed
    {
        $config = $this->loadConfig();
        $apiUrl = rtrim($config['base_url'], '/') . '/api/generate';

        $body = [
            'model' => $config['model'],
            'prompt' => $finalPrompt,
            'stream' => false,
            'format' => [
                'type' => 'object',
                'properties' => [
                    'search' => ['type' => ['string', 'null']],
                    'category_id' => ['type' => ['integer', 'null']],
                    'subcategory_id' => ['type' => ['integer', 'null']],
                    'brand_id' => ['type' => ['integer', 'null']],
                    'provider_id' => ['type' => ['integer', 'null']],
                    'min_price' => ['type' => ['number', 'null']],
                    'max_price' => ['type' => ['number', 'null']],
                    'rating' => ['type' => ['number', 'null']],
                    'type' => ['type' => ['string', 'null']],
                    'sort_by' => ['type' => ['string', 'null']],
                    'sort_dir' => ['type' => ['string', 'null']],
                ],
            ],
        ];

        $response = \Illuminate\Support\Facades\Http::timeout($config['timeout'] ?? 120)
            ->post($apiUrl, $body);

        if (!$response->successful()) {
            throw new \Exception($this->exceptionMessage('Ollama API Error: ' . $response->body(), 'Ollama API Error.'));
        }

        $this->fullResponse = array_merge($response->json(), ['prompt' => $finalPrompt]);

        $decoded = json_decode((string) data_get($this->fullResponse, 'response'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception($this->exceptionMessage('Ollama API Error: Model output is not valid JSON.', 'Ollama API Error.'));
        }

        $this->data = $decoded;

        return $this->data;
    }

    protected function loadConfig(): array
    {
        $plugin = \App\Models\Plugin::where('name', 'Ollama')->first();

        if (!$plugin) {
            throw new \Exception($this->exceptionMessage('Ollama plugin not found.', 'Ollama plugin not available.'));
        }

        return json_decode($plugin->data, true);
    }
}
