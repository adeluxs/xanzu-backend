<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;

class LanguageController extends Controller
{
    use ApiResponse;

    public function changeLanguage($locale)
    {
        session()->put('locale', $locale);

        return $this->successResponse([
            'locale' => $locale,
            'translations_keys' => $this->getTranslationKeys($locale),
        ], __('Language changed successfully'), );
    }

    private function getTranslationKeys($locale)
    {
        $filePath = resource_path("lang/app/$locale.json");
        if (! file_exists($filePath)) {
            return null;
        }

        $translations = json_decode(file_get_contents($filePath), true);

        return $translations;
    }
}
