<?php

namespace App\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Log;
use JsonException;
use JsonSerializable;
use Throwable;

final class JsonData
{
    /**
     * Convert JSON-backed data to an array without allowing malformed legacy
     * database values to reach foreach(), array_merge(), or Fluent.
     */
    public static function decodeArray(mixed $value, array $fallback = [], array $context = []): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        if ($value instanceof JsonSerializable) {
            $value = $value->jsonSerialize();

            return is_array($value) ? $value : $fallback;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        if (! is_string($value) || trim($value) === '') {
            self::reportFallback('empty_or_non_string', $value, $context);

            return $fallback;
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            self::reportFallback('invalid_json', $value, $context, $exception);

            return $fallback;
        }

        if (! is_array($decoded)) {
            self::reportFallback('decoded_value_is_not_an_array', $value, $context);

            return $fallback;
        }

        return $decoded;
    }

    private static function reportFallback(
        string $reason,
        mixed $value,
        array $context,
        ?Throwable $exception = null
    ): void {
        if ($context === []) {
            return;
        }

        Log::warning('JSON_ARRAY_FALLBACK_USED', array_merge($context, [
            'reason' => $reason,
            'value_type' => get_debug_type($value),
            'value_length' => is_string($value) ? strlen($value) : null,
            'json_error' => $exception?->getMessage(),
        ]));
    }
}
