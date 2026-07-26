<?php

namespace Modules\Ai\ReviewSummery\Provider;

class Gemini extends \Modules\Ai\ReviewSummery\Contact
{
    public function process(string $finalPrompt): string
    {
        $config = $this->loadConfig();
        $apiUrl = $this->resolveApiUrl($config);

        $body = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $finalPrompt,
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0,
            ],
        ];

        $response = \Illuminate\Support\Facades\Http::timeout($config['timeout'] ?? 60)
            ->withHeaders([
                'x-goog-api-key' => $config['api_key'] ?? null,
            ])
            ->post($apiUrl, $body);

        if (!$response->successful()) {
            $message = $response->json('error.message') ?? $response->body();
            throw new \Exception($this->exceptionMessage('Gemini API Error: ' . $message, 'Gemini API Error.'));
        }

        $this->fullResponse = array_merge($response->json(), ['prompt' => $finalPrompt]);

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
        if (!is_string($text) || trim($text) === '') {
            throw new \Exception($this->exceptionMessage('Gemini API Error: Unable to parse model output text.', 'Gemini API Error.'));
        }

        return trim($text);
    }

    protected function resolveApiUrl(array $config): string
    {
        if (!empty($config['api_url'])) {
            return $config['api_url'];
        }

        $baseUrl = rtrim($config['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta', '/');
        $model = $config['model'] ?? 'gemini-2.0-flash';

        if (str_starts_with($model, 'models/')) {
            $model = substr($model, strlen('models/'));
        }

        return $baseUrl . '/models/' . $model . ':generateContent';
    }

    protected function loadConfig(): array
    {
        $plugin = \App\Models\Plugin::where('name', 'Gemini')->first();

        if (!$plugin) {
            throw new \Exception($this->exceptionMessage('Gemini plugin not found.', 'Gemini plugin not available.'));
        }

        return json_decode($plugin->data, true);
    }
}
