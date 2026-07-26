<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $themes = DB::table('landing_pages')->distinct()->pluck('theme');

        foreach ($themes as $theme) {
            $locales = DB::table('landing_pages')
                ->where('theme', $theme)
                ->distinct()
                ->pluck('locale')
                ->filter()
                ->values();

            if ($locales->isEmpty()) {
                $locales = collect(['en']);
            }

            foreach ($locales as $locale) {
                foreach ($this->pageDefinitions() as $code => $definition) {
                    $this->syncPage($theme, $locale, $code, $definition);
                }

                foreach ($this->retiredCodes() as $short => $code) {
                    DB::table('landing_pages')
                        ->where('theme', $theme)
                        ->where('locale', $locale)
                        ->where('code', $code)
                        ->update([
                            'status' => 0,
                            'short' => $short,
                            'updated_at' => now(),
                        ]);
                }
            }

            $this->ensureContentGroup($theme, $locales->all(), 'how-it-works', [
                ['title' => 'Choose BNPL at Checkout', 'description' => 'Select Pay with FlexPay when shopping.'],
                ['title' => 'Get Instant Approval', 'description' => 'No credit score impact. Approval in seconds.'],
                ['title' => 'Pay in Installments', 'description' => 'Split into 4 or monthly payments — interest free.'],
            ]);

            $this->ensureContentGroup($theme, $locales->all(), 'stats', [
                ['title' => '$25M+', 'description' => 'Transactions Processed'],
                ['title' => '50,000+', 'description' => 'Active Shoppers'],
                ['title' => '1200+', 'description' => 'Partner Stores'],
                ['title' => '98%', 'description' => 'Approval Rate'],
            ]);

            $this->ensureContentGroup($theme, $locales->all(), 'why-choose-us', [
                ['title' => 'Instant Approval', 'description' => 'No paperwork. No waiting.'],
                ['title' => 'Bank-Level Security', 'description' => 'Your data is fully encrypted.'],
                ['title' => 'Zero Hidden Fees', 'description' => 'What you see is what you pay.'],
                ['title' => 'Manage in One App', 'description' => 'Track payments anytime.'],
            ]);

            $this->ensureContentGroup($theme, $locales->all(), 'about-us', [
                ['title' => 'Transparency', 'description' => 'No hidden charges. Ever.'],
                ['title' => 'Simplicity', 'description' => 'Fast onboarding. Easy payments.'],
                ['title' => 'Security', 'description' => 'Bank-level protection for every transaction.'],
                ['title' => 'Inclusion', 'description' => 'Designed for all shoppers.'],
            ]);
        }
    }

    public function down(): void
    {
        //
    }

    private function pageDefinitions(): array
    {
        return [
            'hero' => [
                'name' => 'Hero Section',
                'short' => 1,
                'status' => 1,
                'defaults' => [
                    'hero_title' => 'Pay Your Way. On Your Terms',
                    'hero_description' => 'Get what you love today and split the cost into simple, predictable installments — no surprises, no stress.',
                    'qr_text' => 'Get the xanzu app',
                    'background_image' => null,
                    'hero_image' => null,
                    'qr_image' => null,
                ],
            ],
            'how-it-works' => [
                'name' => 'How It Works Section',
                'short' => 2,
                'status' => 1,
                'defaults' => [
                    'title' => 'How It Works',
                    'background_image' => null,
                    'right_image' => null,
                ],
            ],
            'stats' => [
                'name' => 'Stats Section',
                'short' => 3,
                'status' => 1,
                'defaults' => [
                    'background_image' => null,
                ],
            ],
            'why-choose-us' => [
                'name' => 'Why Choose Us Section',
                'short' => 4,
                'status' => 1,
                'defaults' => [
                    'title' => 'Why Choose Us',
                ],
            ],
            'about-us' => [
                'name' => 'About Us Section',
                'short' => 5,
                'status' => 1,
                'defaults' => [
                    'title' => 'Redefining How People Pay',
                    'description' => 'We help shoppers buy what they love today and pay over time — without interest, hidden fees, or complicated approvals.',
                    'left_image' => null,
                ],
            ],
            'pay-in-4' => [
                'name' => 'Flexible Payments Section',
                'short' => 6,
                'status' => 1,
                'defaults' => [
                    'title' => '4 Payments. Zero Stress.',
                    'description' => 'Pay over time anywhere VISA is accepted with your Xanzu Card.',
                    'bullet_one' => 'No upfront stress',
                    'bullet_two' => 'Auto payments enabled',
                    'bullet_three' => 'Flexible due dates',
                    'bullet_four' => 'Track payments anytime',
                    'right_image' => null,
                ],
            ],
            'faq' => [
                'name' => 'FAQ Section',
                'short' => 7,
                'status' => 1,
                'defaults' => [
                    'title' => 'Frequently Asked Questions',
                    'background_image' => null,
                ],
            ],
            'testimonials' => [
                'name' => 'Testimonials Section',
                'short' => 8,
                'status' => 1,
                'defaults' => [
                    'testimonial_title' => 'Trusted by Thousands of Smart Shoppers',
                ],
            ],
            'app-link' => [
                'name' => 'App Link Section',
                'short' => 9,
                'status' => 1,
                'defaults' => [
                    'title' => 'Smarter Shopping Starts in Your Pocket',
                    'description' => 'Split payments, stay organized, and never miss a due date again',
                    'background_image' => null,
                    'right_image' => null,
                    'app_store_icon' => null,
                    'app_store_url' => '/',
                    'play_store_icon' => null,
                    'play_store_url' => '/',
                ],
            ],
            'footer' => [
                'name' => 'Footer Section',
                'short' => 10,
                'status' => 1,
                'defaults' => [],
            ],
        ];
    }

    private function retiredCodes(): array
    {
        return [
            90 => 'features',
            91 => 'latest-items',
            92 => 'blog',
            93 => 'flash_sell',
            94 => 'about-stats',
            95 => 'trending-items',
            96 => 'trending-category',
            97 => 'popular-seller',
            98 => 'testimonial',
            99 => 'cta-banner',
        ];
    }

    private function syncPage(string $theme, string $locale, string $code, array $definition): void
    {
        $row = DB::table('landing_pages')
            ->where('theme', $theme)
            ->where('locale', $locale)
            ->where('code', $code)
            ->first();

        $payload = [
            'theme' => $theme,
            'locale' => $locale,
            'code' => $code,
            'name' => $definition['name'],
            'short' => $definition['short'],
            'status' => $row?->status ?? $definition['status'],
            'data' => json_encode($this->mergeData($row?->data, $definition['defaults'])),
            'updated_at' => now(),
        ];

        if ($row) {
            DB::table('landing_pages')
                ->where('id', $row->id)
                ->update($payload);

            return;
        }

        $payload['created_at'] = now();
        DB::table('landing_pages')->insert($payload);
    }

    private function mergeData(?string $existingData, array $defaults): array
    {
        $decoded = json_decode($existingData ?? '[]', true);

        if (!is_array($decoded)) {
            $decoded = [];
        }

        return array_merge($defaults, $decoded);
    }

    private function ensureContentGroup(string $theme, array $locales, string $type, array $items): void
    {
        if (empty($locales)) {
            return;
        }

        $maxLocaleId = (int) DB::table('landing_contents')->max('locale_id');

        foreach ($items as $index => $item) {
            $existingEnglish = DB::table('landing_contents')
                ->where('theme', $theme)
                ->where('locale', 'en')
                ->where('type', $type)
                ->orderBy('id')
                ->skip($index)
                ->first();

            $localeId = $existingEnglish?->locale_id ?: ++$maxLocaleId;

            foreach ($locales as $locale) {
                $row = DB::table('landing_contents')
                    ->where('theme', $theme)
                    ->where('locale', $locale)
                    ->where('type', $type)
                    ->where('locale_id', $localeId)
                    ->first();

                if ($row) {
                    continue;
                }

                DB::table('landing_contents')->insert([
                    'theme' => $theme,
                    'icon' => null,
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'photo' => null,
                    'type' => $type,
                    'locale_id' => $localeId,
                    'locale' => $locale,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
