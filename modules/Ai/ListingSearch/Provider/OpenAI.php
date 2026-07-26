<?php

namespace Modules\Ai\ListingSearch\Provider;

class OpenAI extends \Modules\Ai\ListingSearch\Contact
{
    public function process(string $finalPrompt): mixed
    {
        $config = $this->loadConfig();
        $apiUrl = $config['api_url']
            ?? (rtrim($config['base_url'] ?? 'https://api.openai.com/v1', '/') . '/responses');

        $body = [
            'model' => $config['model'] ?? 'gpt-4o-mini',
            'input' => $finalPrompt,
            'temperature' => 0,
            'text' => [
                'format' => [
                    'type' => 'json_object',
                ],
            ],
        ];

        $request = \Illuminate\Support\Facades\Http::timeout($config['timeout'] ?? 60)
            ->withToken($config['api_key'] ?? null);

        if (!empty($config['organization'])) {
            $request = $request->withHeaders([
                'OpenAI-Organization' => $config['organization'],
            ]);
        }

        if (!empty($config['project'])) {
            $request = $request->withHeaders([
                'OpenAI-Project' => $config['project'],
            ]);
        }

        $response = $request->post($apiUrl, $body);

        if ($response->json('error')) {
            throw new \Exception($this->exceptionMessage(
                'OpenAI API Error: ' . $response->json('error.message', 'Unknown error'),
                'OpenAI API Error.'
            ));
        }

        $this->fullResponse = array_merge($response->json(), ['prompt' => $finalPrompt]);

        $text = data_get($response->json(), 'output.0.content.0.text')
            ?: data_get($response->json(), 'choices.0.message.content');

        if (!is_string($text) || trim($text) === '') {
            throw new \Exception($this->exceptionMessage('OpenAI API Error: Unable to parse model output text.', 'OpenAI API Error.'));
        }

        $decoded = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception($this->exceptionMessage('OpenAI API Error: Model output is not valid JSON.', 'OpenAI API Error.'));
        }

        $this->data = $decoded;

        return $this->data;
    }

    protected function loadConfig(): array
    {
        $plugin = \App\Models\Plugin::where('name', 'OpenAI')->first();

        if (!$plugin) {
            throw new \Exception($this->exceptionMessage('OpenAI plugin not found.', 'OpenAI plugin not available.'));
        }

        return json_decode($plugin->data, true);
    }
}
