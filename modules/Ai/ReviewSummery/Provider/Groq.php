<?php

namespace Modules\Ai\ReviewSummery\Provider;

class Groq extends \Modules\Ai\ReviewSummery\Contact
{
    public function process(string $finalPrompt): string
    {
        $config = $this->loadConfig();
        $apiUrl = rtrim($config['api_url'], '/');

        $body = [
            'model' => $config['model'],
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
        if (!is_string($text) || trim($text) === '') {
            throw new \Exception($this->exceptionMessage('Groq API Error: Unable to parse model output text.', 'Groq API Error.'));
        }

        return trim($text);
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
