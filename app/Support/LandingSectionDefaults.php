<?php

namespace App\Support;

final class LandingSectionDefaults
{
    public static function get(?string $code): array
    {
        $asset = static fn (string $path): string => self::frontendAsset($path);

        return match ($code) {
            'hero' => [
                'hero_title' => 'Pay Your Way. On Your Terms',
                'hero_description' => 'Get what you love today and split the cost into simple, predictable installments — no surprises, no stress.',
                'qr_text' => 'Get the MozaPay app',
                'background_image' => $asset('landing-page/hero-section/hero-bg.png'),
                'hero_image' => $asset('landing-page/hero-section/hero-img.png'),
                'qr_image' => $asset('landing-page/hero-section/QR-Code.png'),
            ],
            'how-it-works' => [
                'title' => 'How It Works',
                'background_image' => $asset('landing-page/how-its-works/how-its-works-bg.png'),
                'right_image' => $asset('landing-page/how-its-works/how-it-works.png'),
            ],
            'stats' => [
                'background_image' => $asset('landing-page/stats/stats-bg.png'),
            ],
            'why-choose-us' => [
                'title' => 'Why Choose Us',
            ],
            'about-us' => [
                'title' => 'Redefining How People Pay',
                'description' => 'We help shoppers buy what they love today and pay over time — without interest, hidden fees, or complicated approvals.',
                'background_image' => $asset('landing-page/about-us/about-us-bg.png'),
                'left_image' => $asset('landing-page/about-us/about-us.png'),
            ],
            'pay-in-4' => [
                'title' => '4 Payments. Zero Stress.',
                'description' => 'Pay over time anywhere VISA is accepted with your MozaPay Card.',
                'bullet_one' => 'No upfront stress',
                'bullet_two' => 'Auto payments enabled',
                'bullet_three' => 'Flexible due dates',
                'bullet_four' => 'Track payments anytime',
                'right_image' => $asset('landing-page/how-to-do/how-to-do.png'),
            ],
            'faq' => [
                'title' => 'Frequently Asked Questions',
                'background_image' => $asset('landing-page/faq/faq-bg.png'),
            ],
            'testimonials' => [
                'testimonial_title' => 'Trusted by Thousands of Smart Shoppers',
            ],
            'app-link' => [
                'title' => 'Smarter Shopping Starts in Your Pocket',
                'description' => 'Split payments, stay organized, and never miss a due date again.',
                'background_image' => $asset('landing-page/app-link/app-link-bg.png'),
                'right_image' => $asset('landing-page/app-link/app-img.png'),
                'app_store_icon' => $asset('landing-page/app-link/app-store-icon.png'),
                'app_store_url' => '/',
                'play_store_icon' => $asset('landing-page/app-link/play-store-icon.png'),
                'play_store_url' => '/',
            ],
            default => [],
        };
    }

    /**
     * Keep configured values, but do not let blank legacy values erase the
     * bundled image/text defaults that make a section renderable.
     */
    public static function merge(?string $code, array $configured): array
    {
        $merged = self::get($code);

        foreach ($configured as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }

    private static function frontendAsset(string $path): string
    {
        $frontendUrl = rtrim((string) config('app.frontend_url', 'https://mozapay.app'), '/');

        return $frontendUrl.'/assets/'.ltrim($path, '/');
    }
}
