<?php

namespace App\Services;

use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use SplFileInfo;

class TranslationService
{
    private const PER_PAGE = 50;

    private const CACHE_TTL = 600; // 10 minutes

    public function translations(string $locale = 'en'): array
    {
        $cacheKey = "translations_{$locale}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($locale) {
            return array_merge(
                $this->loadJsonTranslations($locale),
                $this->loadPhpTranslations($locale)
            );
        });
    }

    public function paginate(
        string $locale = 'en',
        int $perPage = self::PER_PAGE,
        ?int $page = null,
        ?string $search = null
    ): LengthAwarePaginator {
        $translations = $this->translations($locale);
        $collection = collect($translations);

        // Apply search
        if ($search) {
            $searchLower = strtolower($search);
            $collection = $collection->filter(function ($value, $key) use ($searchLower) {
                return str_contains(strtolower($key), $searchLower) ||
                    (is_string($value) && str_contains(strtolower($value), $searchLower));
            });
        }

        // Get current page
        $page = $page ?: request()->get('page', 1);

        return new LengthAwarePaginator(
            $collection->forPage($page, $perPage)->all(),
            $collection->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    public function updateTranslation(string $locale, string $key, string $value): bool
    {
        try {
            // Detect if JSON translation or a PHP translation
            if (strpos($key, '.') === false) {
                return $this->updateJsonTranslation($locale, $key, $value);
            }

            // PHP translation
            return $this->updatePhpTranslation($locale, $key, $value);
        } catch (Exception $e) {
            Log::error("Error updating translation [{$key}] for locale [{$locale}]: ".$e->getMessage());

            return false;
        }
    }

    private function updateJsonTranslation(string $locale, string $key, string $value): bool
    {
        $jsonPath = lang_path("{$locale}.json");

        try {
            $jsonContent = File::get($jsonPath);
            $translations = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($translations)) {
                $translations = [];
            }

            // Update the translation
            $translations[$key] = $value;

            // Write back to file
            File::put($jsonPath, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Clear cache
            $this->clearTranslationCache($locale);

            return true;
        } catch (Exception $e) {
            Log::error("Error updating JSON translation [{$key}] for locale [{$locale}]: ".$e->getMessage());

            return false;
        }
    }

    private function updatePhpTranslation(string $locale, string $key, string $value): bool
    {
        // Parse the key to extract filename and actual key
        $parts = explode('.', $key, 2);
        if (count($parts) !== 2) {
            return false;
        }

        $filename = $parts[0];
        $translationKey = $parts[1];

        $filePath = lang_path("{$locale}/{$filename}.php");

        // Create directory if it doesn't exist
        if (! File::exists(lang_path($locale))) {
            File::makeDirectory(lang_path($locale), 0755, true);
        }

        try {
            // Load current translations or create new array
            $translations = [];
            if (File::exists($filePath)) {
                $translations = include $filePath;
                if (! is_array($translations)) {
                    $translations = [];
                }
            }

            // Handle nested keys
            $keys = explode('.', $translationKey);
            $lastKey = array_pop($keys);
            $current = &$translations;

            // Create nested keys
            foreach ($keys as $nestedKey) {
                if (! isset($current[$nestedKey]) || ! is_array($current[$nestedKey])) {
                    $current[$nestedKey] = [];
                }
                $current = &$current[$nestedKey];
            }

            // Set the value
            $current[$lastKey] = $value;

            // Create or update the PHP file
            $content = "<?php\n\nreturn ".$this->varExport($translations, true).";\n";
            File::put($filePath, $content);

            // Clear cache
            $this->clearTranslationCache($locale);

            return true;
        } catch (Exception $e) {
            Log::error("Error updating PHP translation [{$key}] for locale [{$locale}]: ".$e->getMessage());

            return false;
        }
    }

    private function varExport($expression, $return = false)
    {
        $export = var_export($expression, true);
        $export = preg_replace("/array \(([^;]*)\)/s", "[\n$1\n]", $export);
        $export = preg_replace("/=> \n\s+\[/", '=> [', $export);
        $export = preg_replace("/(\s+)[\d]+ => /", '$1', $export);

        if ($return) {
            return $export;
        }
        echo $export;
    }

    public function clearTranslationCache(?string $locale = null): void
    {
        if ($locale) {
            Cache::forget("translations_{$locale}");
        } else {
            $locales = $this->getAvailableLocales();
            foreach ($locales as $loc) {
                Cache::forget("translations_{$loc}");
            }
        }
    }

    public function getAvailableLocales(): array
    {
        return Cache::remember('available_locales', self::CACHE_TTL, function () {
            $locales = [];

            // Get JSON translation files
            foreach (File::files(lang_path()) as $file) {
                if ($file->getExtension() === 'json') {
                    $locales[] = $file->getFilenameWithoutExtension();
                }
            }

            // Get all locales
            foreach (File::directories(lang_path()) as $directory) {
                $locales[] = basename($directory);
            }

            return array_unique($locales);
        });
    }

    private function loadJsonTranslations(string $locale): array
    {
        $jsonPath = lang_path("{$locale}.json");
        if (! File::exists($jsonPath)) {
            return [];
        }

        try {
            $jsonContent = File::get($jsonPath);

            return json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR) ?? [];
        } catch (Exception $e) {
            Log::error("Error loading JSON translations from {$jsonPath}: ".$e->getMessage());

            return [];
        }
    }

    private function loadPhpTranslations(string $locale): array
    {
        $phpPath = lang_path($locale);
        if (! File::exists($phpPath)) {
            return [];
        }

        $translations = [];
        foreach (File::allFiles($phpPath) as $file) {
            $fileTranslations = $this->processPhpFile($file);
            if (! empty($fileTranslations)) {
                $translations = array_merge($translations, $fileTranslations);
            }
        }

        return $translations;
    }

    private function processPhpFile(SplFileInfo $file): array
    {
        $filename = pathinfo($file, PATHINFO_FILENAME);
        try {
            $fileTranslations = include $file;

            throw_unless(is_array($fileTranslations), Exception::class, 'Invalid PHP translation file format');

            return $this->flattenTranslations($filename, $fileTranslations);
        } catch (Exception $e) {
            Log::error("Error processing translation file {$file->getFilename()}: ".$e->getMessage());

            return [];
        }
    }

    private function flattenTranslations(string $filename, array $translations): array
    {
        $flattened = [];
        foreach ($translations as $key => $value) {
            if (is_array($value)) {
                foreach (Arr::dot([$key => $value]) as $flatKey => $flatValue) {
                    $flattened["{$filename}.{$flatKey}"] = $flatValue;
                }
            } else {
                $flattened["{$filename}.{$key}"] = $value;
            }
        }

        return $flattened;
    }
}
