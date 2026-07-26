<?php

namespace Modules\Ai\ReviewSummery\Provider;

class Ollama extends \Modules\Ai\ReviewSummery\Contact
{
    public function process(string $finalPrompt): string
    {
        $config = $this->loadConfig();
        $apiUrl = rtrim($config['base_url'], '/') . '/api/generate';

        $body = [
            'model' => $config['model'],
            'prompt' => $finalPrompt,
            'stream' => false,
        ];

        $response = \Illuminate\Support\Facades\Http::timeout($config['timeout'] ?? 120)
            ->post($apiUrl, $body);

        if (!$response->successful()) {
            throw new \Exception($this->exceptionMessage('Ollama API Error: ' . $response->body(), 'Ollama API Error.'));
        }

        $this->fullResponse = array_merge($response->json(), ['prompt' => $finalPrompt]);

        $text = (string) data_get($this->fullResponse, 'response');
        if (trim($text) === '') {
            throw new \Exception($this->exceptionMessage('Ollama API Error: Unable to parse model output text.', 'Ollama API Error.'));
        }

        return trim($text);
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
