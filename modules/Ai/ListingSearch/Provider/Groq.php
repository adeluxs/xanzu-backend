<?php

namespace Modules\Ai\ListingSearch\Provider;

class Groq extends \Modules\Ai\ListingSearch\Contact
{
    public function process(string $finalPrompt): mixed
    {
        $config = $this->loadConfig();
        $apiUrl = rtrim($config['api_url'], '/');

        $body = [
            'model' => $config['model'],
            'response_format' => [
                'type' => 'json_object',
            ],
            'messages' => [
                ['role' => 'user', 'content' => $finalPrompt],
            ],
        ];

        $response = \Illuminate\Support\Facades\Http::timeout($config['timeout'] ?? 120)
            ->withToken($config['api_key'] ?? null)
            ->post($apiUrl, $body);

        if ($response->json('error')) {
            throw new \Exception($this->exceptionMessage(
                'Groq API Error: ' . $response->json('error.message', 'Unknown error'),
                'Groq API Error.'
            ));
        }

        $this->fullResponse = array_merge($response->json(), ['prompt' => $finalPrompt]);

        $text = data_get($response->json(), 'choices.0.message.content');
        $decoded = json_decode((string) $text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception($this->exceptionMessage('Groq API Error: Model output is not valid JSON.', 'Groq API Error.'));
        }

        $this->data = $decoded;

        return $this->data;
    }

    protected function loadConfig(): array
    {
        $plugin = \App\Models\Plugin::where('name', 'Groq')->first();

        if (!$plugin) {
            throw new \Exception($this->exceptionMessage('Groq plugin not found.', 'Groq plugin not available.'));
        }

        return json_decode($plugin->data, true);
    }
}
