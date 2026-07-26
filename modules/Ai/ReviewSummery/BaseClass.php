<?php

namespace Modules\Ai\ReviewSummery;

use Modules\Ai\ReviewSummery\Provider\Gemini;
use Modules\Ai\ReviewSummery\Provider\Groq;
use Modules\Ai\ReviewSummery\Provider\Ollama;
use Modules\Ai\ReviewSummery\Provider\OpenAI;
use Modules\Ai\ReviewSummery\Traits\HasEnvironmentAwareErrors;

class BaseClass
{
    use HasEnvironmentAwareErrors;

    public function summarize(array $reviews): ?string
    {
        $reviews = array_values(array_filter(array_map(static function ($value) {
            return is_string($value) ? trim($value) : '';
        }, $reviews)));

        if (empty($reviews)) {
            return null;
        }

        $provider = strtolower(setting('ai_provider', 'system'));
        $providerClass = $this->getProvider($provider);

        if (!$providerClass) {
            throw new \Exception($this->exceptionMessage('Provider not found.', 'Provider not available.'));
        }

        $contact = new $providerClass();
        $summary = $contact->process($this->preparePrompt($reviews));

        return $this->normalizeSummary($summary);
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

    protected function preparePrompt(array $reviews): string
    {
        $template = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'prompt.txt');
        $reviewLines = implode(PHP_EOL, array_map(static fn($review) => '- ' . $review, $reviews));

        return str_replace('{{reviews}}', $reviewLines, $template);
    }

    protected function normalizeSummary(string $summary): ?string
    {
        $trimmed = trim($summary);
        if ($trimmed === '') {
            return null;
        }

        $items = [];

        preg_match_all('/<li\b[^>]*>(.*?)<\/li>/is', $trimmed, $liMatches);
        if (!empty($liMatches[1])) {
            foreach ($liMatches[1] as $liText) {
                $clean = trim(strip_tags((string) $liText));
                if ($clean !== '') {
                    $items[] = $clean;
                }
            }
        }

        if (empty($items)) {
            $plainText = strip_tags($trimmed);
            $lines = preg_split('/\r\n|\r|\n/', $plainText) ?: [];

            foreach ($lines as $line) {
                $clean = trim((string) $line);
                if ($clean === '') {
                    continue;
                }

                $clean = preg_replace('/^\s*(?:[-*]|\d+[.)])\s*/', '', $clean) ?? '';
                $clean = trim($clean);
                if ($clean === '') {
                    continue;
                }

                $items[] = $clean;
            }
        }

        $normalized = [];
        foreach ($items as $item) {
            $words = preg_split('/\s+/', $item) ?: [];
            if (count($words) > 15) {
                $item = implode(' ', array_slice($words, 0, 15));
            }

            if ($item !== '') {
                $normalized[] = $item;
            }

            if (count($normalized) === 5) {
                break;
            }
        }

        $normalized = array_values(array_unique($normalized));
        if (empty($normalized)) {
            return null;
        }

        $html = '<ol>';
        foreach ($normalized as $point) {
            $html .= '<li>' . e($point) . '</li>';
        }
        $html .= '</ol>';

        return $html;
    }
}
