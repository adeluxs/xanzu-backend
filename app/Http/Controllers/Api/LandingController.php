<?php

namespace App\Http\Controllers\Api;

use App\Enums\NavigationType;
use App\Http\Controllers\Controller;
use App\Models\LandingContent;
use App\Models\LandingPage;
use App\Models\Language;
use App\Models\Navigation;
use App\Models\Page;
use App\Models\Social;
use App\Models\Testimonial;
use App\Support\JsonData;
use App\Support\LandingCache;
use App\Support\LandingSectionDefaults;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LandingController extends Controller
{
    use ApiResponse;

    private const THEME = 'default';
    private const CACHE_PREFIX = 'api.landing';
    private const CACHE_TTL_MINUTES = 10;
    private const LOCALE_CACHE_TTL_MINUTES = 60;
    private const DEFAULT_LOCALE_CACHE_TTL_MINUTES = 1440;
    private const SECTION_CODE_ALIASES = [
        'testimonial' => 'testimonials',
    ];

    public function index(?string $lang = null, ?string $code = null): JsonResponse
    {
        $defaultLocale = $this->defaultLocale();
        $lang = $this->normalizeSectionCode($lang);
        $code = $this->normalizeSectionCode($code);

        if ($code === null && filled($lang) && $this->isLandingCode($lang)) {
            $code = $lang;
            $lang = null;
        }

        $locale = $this->resolveLocale($lang, $defaultLocale);

        if ($code !== null) {
            $payload = $this->rememberCache(
                $this->cacheKey('landing.section', [$locale, $defaultLocale, $code]),
                function () use ($locale, $defaultLocale, $code) {
                    $page = $this->resolvePageByCode($locale, $defaultLocale, $code);

                    if (!$page) {
                        return ['missing' => true];
                    }

                    return [
                        'data' => [
                            'language' => $locale,
                            'theme' => self::THEME,
                            'section' => $this->formatPage(
                                $page,
                                $this->resolveContentsMap($locale, $defaultLocale, collect([$page->code]))
                            ),
                        ],
                        'message' => 'Landing page section fetched successfully',
                    ];
                }
            );

            if ($payload['missing'] ?? false) {
                return $this->notFoundResponse('Landing page section not found');
            }

            return $this->successResponse(
                data: $payload['data'],
                message: $payload['message'],
            );
        }

        $payload = $this->rememberCache(
            $this->cacheKey('landing.index', [$locale, $defaultLocale]),
            function () use ($locale, $defaultLocale) {
                $pages = $this->resolveAllPages($locale, $defaultLocale);
                $contentsMap = $this->resolveContentsMap($locale, $defaultLocale, $pages->pluck('code'));

                return [
                    'data' => [
                        'language' => $locale,
                        'theme' => self::THEME,
                        'sections' => $pages
                            ->map(fn(LandingPage $page) => $this->formatPage($page, $contentsMap))
                            ->values(),
                    ],
                    'message' => 'Landing page data fetched successfully',
                    'meta' => [
                        'title' => setting('meta_title', 'meta') . ' - ' . setting('site_title', 'site_name'),
                        'description' => setting('meta_description', 'meta'),
                        'keywords' => setting('meta_keywords', 'meta'),
                        'favicon' => asset(setting('site_favicon', 'global')),
                    ],
                ];
            }
        );

        return $this->successResponse(
            data: $payload['data'],
            message: $payload['message'],
            meta: $payload['meta'],
        );
    }

    public function pages(?string $lang = null, ?string $code = null): JsonResponse
    {
        $defaultLocale = $this->defaultLocale();

        if ($code === null && filled($lang) && $this->isPageCode($lang)) {
            $code = $lang;
            $lang = null;
        }

        $locale = $this->resolveLocale($lang, $defaultLocale);

        if (!$code and $lang != $locale) {
            return $this->notFoundResponse('Page not found');
        }

        if ($code !== null) {
            $payload = $this->rememberCache(
                $this->cacheKey('pages.single', [$locale, $defaultLocale, $code, $this->planCacheState()]),
                function () use ($locale, $defaultLocale, $code) {

                    $page = $this->resolveContentPageByCode($locale, $defaultLocale, $code);

                    if (!$page) {
                        return ['missing' => true];
                    }

                    return [
                        'data' => [
                            'language' => $locale,
                            'theme' => self::THEME,
                            'page' => $this->formatContentPage($page, $locale, $defaultLocale),
                        ],
                        'message' => 'Page fetched successfully',
                    ];
                }
            );

            if ($payload['missing'] ?? false) {
                return $this->notFoundResponse('Page not found');
            }

            return $this->successResponse(
                data: $payload['data'],
                message: $payload['message'],
                meta: [
                    'favicon' => asset(setting('site_favicon', 'global')),
                ]
            );
        }

        $payload = $this->rememberCache(
            $this->cacheKey('pages.index', [$locale, $defaultLocale, $this->planCacheState()]),
            function () use ($locale, $defaultLocale) {
                $pages = $this->resolveAllContentPages($locale, $defaultLocale);

                return [
                    'data' => [
                        'language' => $locale,
                        'theme' => self::THEME,
                        'pages' => $pages
                            ->map(fn(Page $page) => $this->formatContentPage($page, $locale, $defaultLocale))
                            ->values(),
                    ],
                    'message' => 'Pages fetched successfully',
                ];
            }
        );

        return $this->successResponse(
            data: $payload['data'],
            message: $payload['message'],
            meta: [
                'favicon' => asset(setting('site_favicon', 'global')),
            ]
        );
    }

    private function defaultLocale(): string
    {
        return $this->rememberCache(
            $this->cacheKey('default-locale'),
            fn() => Language::query()
                ->where('is_default', true)
                ->value('locale')
            ?? config('app.locale', 'en'),
            self::DEFAULT_LOCALE_CACHE_TTL_MINUTES,
        );
    }

    private function normalizeSectionCode(?string $code): ?string
    {
        if (!is_string($code) || $code === '') {
            return $code;
        }

        return self::SECTION_CODE_ALIASES[$code] ?? $code;
    }

    private function isLandingCode(string $value): bool
    {
        return $this->rememberCache(
            $this->cacheKey('landing-code-exists', [$value]),
            fn() => LandingPage::query()
                ->where('theme', self::THEME)
                ->where('code', $value)
                ->exists(),
            self::LOCALE_CACHE_TTL_MINUTES,
        );
    }

    private function isPageCode(string $value): bool
    {
        return $this->rememberCache(
            $this->cacheKey('page-code-exists', [$value, $this->planCacheState()]),
            fn() => Page::query()
                ->where('theme', self::THEME)
                ->where('status', true)
                ->whereAny(['code', 'url'], $value)
                ->exists(),
            self::LOCALE_CACHE_TTL_MINUTES,
        );
    }

    private function resolveLocale(?string $lang, string $defaultLocale): string
    {
        if (blank($lang)) {
            return $defaultLocale;
        }

        $exists = $this->rememberCache(
            $this->cacheKey('locale-active', [$lang]),
            fn() => Language::query()
                ->where('locale', $lang)
                ->where('status', true)
                ->exists(),
            self::LOCALE_CACHE_TTL_MINUTES,
        );

        return $exists ? $lang : $defaultLocale;
    }

    private function resolveAllPages(string $locale, string $defaultLocale): Collection
    {
        $defaultPages = LandingPage::query()
            ->where('theme', self::THEME)
            ->where('locale', $defaultLocale)
            ->whereNotIn('code', ['footer'])
            ->orderBy('short')
            ->get()
            ->keyBy('code');

        if ($locale === $defaultLocale) {
            return $defaultPages->values();
        }

        $localizedPages = LandingPage::query()
            ->where('theme', self::THEME)
            ->where('locale', $locale)
            ->whereIn('code', $defaultPages->keys()->all())
            ->get()
            ->keyBy('code');

        return $defaultPages
            ->map(fn(LandingPage $page, string $code) => $localizedPages->get($code, $page))
            ->values();
    }

    private function resolvePageByCode(?string $locale, string $defaultLocale, string $code): ?LandingPage
    {
        $locale = $locale ?? $defaultLocale;
        $page = LandingPage::query()
            ->where('theme', self::THEME)
            ->where('locale', $locale)
            ->whereAny(['code'], $code)
            ->first();
        if ($page) {
            return $page;
        }

        return LandingPage::query()
            ->where('theme', self::THEME)
            ->where('locale', $defaultLocale)
            ->whereAny(['code', 'id', 'url'], $code)
            ->first();
    }

    private function resolveAllContentPages(string $locale, string $defaultLocale): Collection
    {
        $defaultPages = $this->contentPagesQuery($defaultLocale)
            ->orderBy('id')
            ->get()
            ->keyBy('code');

        if ($locale === $defaultLocale) {
            return $defaultPages->values();
        }

        $localizedPages = $this->contentPagesQuery($locale)
            ->whereIn('code', $defaultPages->keys()->all())
            ->orderBy('id')
            ->get()
            ->keyBy('code');

        return $defaultPages
            ->map(fn(Page $page, string $code) => $localizedPages->get($code, $page))
            ->values();
    }

    private function resolveContentPageByCode(string $locale, string $defaultLocale, string $code): ?Page
    {
        $page = $this->contentPagesQuery($locale)
            ->whereAny(['code', 'id', 'url'], $code)
            ->first();

        if ($page) {
            return $page;
        }

        return $this->contentPagesQuery($defaultLocale)
            ->whereAny(['code', 'id', 'url'], $code)
            ->first();
    }

    private function resolveContentsMap(string $locale, string $defaultLocale, Collection $codes): Collection
    {
        $types = $codes->filter()->unique()->sort()->values()->all();

        if (empty($types)) {
            return collect();
        }

        $defaultContents = LandingContent::query()
            ->where('theme', self::THEME)
            ->where('locale', $defaultLocale)
            ->whereIn('type', $types)
            ->orderBy('id')
            ->get()
            ->groupBy('type');

        if ($locale === $defaultLocale) {
            $contentsMap = $defaultContents;
        } else {
            $localizedContents = LandingContent::query()
                ->where('theme', self::THEME)
                ->where('locale', $locale)
                ->whereIn('type', $types)
                ->orderBy('id')
                ->get()
                ->groupBy('type');

            $contentsMap = collect($types)->mapWithKeys(function (string $type) use ($localizedContents, $defaultContents) {
                $items = $localizedContents->get($type);

                return [$type => $items && $items->isNotEmpty() ? $items : $defaultContents->get($type, collect())];
            });
        }

        if (in_array('testimonials', $types, true)) {
            $contentsMap->put('testimonials', $this->resolveTestimonials($locale, $defaultLocale));
        }

        return $contentsMap;
    }

    private function resolveTestimonials(string $locale, string $defaultLocale): Collection
    {
        $defaultTestimonials = Testimonial::query()
            ->where('locale', $defaultLocale)
            ->whereColumn('id', 'locale_id')
            ->orderBy('id')
            ->get();

        if ($locale === $defaultLocale || $defaultTestimonials->isEmpty()) {
            return $defaultTestimonials;
        }

        $localizedTestimonials = Testimonial::query()
            ->where('locale', $locale)
            ->whereIn('locale_id', $defaultTestimonials->pluck('locale_id'))
            ->get()
            ->keyBy('locale_id');

        return $defaultTestimonials->map(
            fn(Testimonial $testimonial) => $localizedTestimonials->get($testimonial->locale_id, $testimonial)
        );
    }

    private function formatPage(LandingPage $page, Collection $contentsMap): array
    {
        $data = JsonData::decodeArray($page->getAttribute('data'), [], [
            'model' => 'LandingPage',
            'record_id' => $page->id,
            'code' => $page->code,
            'locale' => $page->locale,
        ]);
        $data = LandingSectionDefaults::merge($page->code, $data);

        return [
            'id' => $page->id,
            'name' => $page->name,
            'code' => $page->code,
            'theme' => $page->theme,
            'locale' => $page->locale,
            'status' => (bool) $page->status,
            'short' => $page->short,
            'data' => $this->transformImageValues($data),
            'contents' => $this->formatPageContents($page->code, $contentsMap),
        ];
    }

    private function formatContentPage(Page $page, string $locale, string $defaultLocale): array
    {
        $data = JsonData::decodeArray($page->getAttribute('data'), [], [
            'model' => 'Page',
            'record_id' => $page->id,
            'code' => $page->code,
            'locale' => $page->locale,
        ]);

        $sectionIds = $this->extractSectionIds($data);
        $sections = $this->resolveLinkedLandingSections($locale, $defaultLocale, $sectionIds);
        $contentsMap = $this->resolveContentsMap($locale, $defaultLocale, $sections->pluck('code'));

        return [
            'id' => $page->id,
            'title' => $page->title,
            'code' => $page->code,
            'url' => $page->url,
            'type' => $page->type,
            'theme' => $page->theme,
            'locale' => $page->locale,
            'status' => (bool) $page->status,
            'data' => $this->transformPageData($data),
            'sections' => $sections
                ->map(fn(LandingPage $section) => $this->formatPage($section, $contentsMap))
                ->values(),
        ];
    }

    private function formatPageContents(string $pageCode, Collection $contentsMap): Collection
    {

        if ($pageCode == 'footer') {
            return $contentsMap
                ->map(fn(Social $social) => [
                    'id' => $social->id,
                    'icon_name' => $social->icon_name,
                    'url' => $social->url,
                ])
                ->sortBy('position')
                ->values();
        }

        $contents = $contentsMap->get($pageCode, collect());


        if ($pageCode === 'testimonials') {
            return $contents
                ->map(fn(Testimonial $testimonial) => $this->formatTestimonialContent($testimonial))
                ->values();
        }



        return $contents
            ->map(fn(LandingContent $content) => $this->formatContent($content))
            ->values();
    }

    private function formatContent(LandingContent $content): array
    {
        return [
            'id' => $content->id,
            'locale_id' => $content->locale_id,
            'theme' => $content->theme,
            'locale' => $content->locale,
            'type' => $content->type,
            'title' => $content->title,
            'description' => $content->description,
            'icon' => $this->toAssetUrl($content->icon, 'icon'),
            'photo' => $this->toAssetUrl($content->photo, 'photo'),
        ];
    }

    private function formatTestimonialContent(Testimonial $testimonial): array
    {
        $picture = $this->toAssetUrl($testimonial->picture, 'picture');

        return [
            'id' => $testimonial->id,
            'locale' => $testimonial->locale,
            'description' => $testimonial->message,
            'name' => $testimonial->name,
            'designation' => $testimonial->designation,
            'message' => $testimonial->message,
            'picture' => $picture,
            'star' => (int) ($testimonial->star ?? 0),
        ];
    }

    private function transformImageValues(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->transformImageValues($value);

                continue;
            }

            if (is_string($value) && $this->shouldConvertToAssetUrl($key, $value)) {
                $data[$key] = asset($value);
            }
        }

        return $data;
    }

    private function transformPageData(array $data): array
    {
        if (array_key_exists('section_id', $data)) {
            $data['section_id'] = $this->extractSectionIds($data)->all();
        }

        return $this->transformImageValues($data);
    }

    private function toAssetUrl(?string $value, string $key): ?string
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        return $this->shouldConvertToAssetUrl($key, $value) ? asset($value) : $value;
    }

    private function shouldConvertToAssetUrl(string $key, string $value): bool
    {
        if ($value === '' || Str::startsWith($value, ['http://', 'https://', '//', 'data:'])) {
            return false;
        }

        $normalizedKey = Str::lower($key);
        $hasImageLikeKey = Str::contains($normalizedKey, [
            'image',
            'img',
            'icon',
            'photo',
            'picture',
            'avatar',
            'logo',
            'qr_image',
        ]);

        $path = parse_url($value, PHP_URL_PATH) ?: $value;
        $hasImageLikeExtension = (bool) preg_match('/\.(png|jpe?g|gif|svg|webp|avif)$/i', $path);

        return $hasImageLikeKey || $hasImageLikeExtension;
    }

    private function contentPagesQuery(string $locale)
    {
        return Page::query()
            ->where('theme', self::THEME)
            ->where('locale', $locale)
            ->where('status', true);
    }

    private function extractSectionIds(array $data): Collection
    {
        $sectionIds = $data['section_id'] ?? [];

        if (is_string($sectionIds)) {
            $decoded = json_decode($sectionIds, true);
            $sectionIds = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($sectionIds)) {
            $sectionIds = [];
        }

        return collect($sectionIds)
            ->filter(fn($id) => filled($id))
            ->map(fn($id) => (int) $id)
            ->values();
    }

    private function resolveLinkedLandingSections(string $locale, string $defaultLocale, Collection $sectionIds): Collection
    {
        if ($sectionIds->isEmpty()) {
            return collect();
        }

        $codes = LandingPage::query()
            ->where('theme', self::THEME)
            ->whereIn('id', $sectionIds->all())
            ->get(['id', 'code'])
            ->keyBy('id');

        $orderedCodes = $sectionIds
            ->map(fn(int $id) => optional($codes->get($id))->code)
            ->filter()
            ->values();

        if ($orderedCodes->isEmpty()) {
            return collect();
        }

        $defaultSections = LandingPage::query()
            ->where('theme', self::THEME)
            ->where('locale', $defaultLocale)
            ->whereIn('code', $orderedCodes->all())
            ->get()
            ->keyBy('code');

        $localizedSections = $locale === $defaultLocale
            ? collect()
            : LandingPage::query()
                ->where('theme', self::THEME)
                ->where('locale', $locale)
                ->whereIn('code', $orderedCodes->all())
                ->get()
                ->keyBy('code');

        return $orderedCodes
            ->map(fn(string $code) => $localizedSections->get($code, $defaultSections->get($code)))
            ->filter()
            ->values();
    }


    public function navigation($lang = null)
    {
        $defaultLocale = $this->defaultLocale();
        $lang = $this->normalizeSectionCode($lang);
        $locale = $this->resolveLocale($lang, $defaultLocale);

        $payload = $this->rememberCache(
            $this->cacheKey('navigation', [$locale, $defaultLocale, $this->planCacheState()]),
            function () use ($locale, $defaultLocale) {
                $header = Navigation::query()
                    ->with('page:id,code')
                    ->where('status', true)
                    ->whereJsonContains('type', NavigationType::Header->value)
                    ->when(!isPlanModuleEnabled(), function ($query) {
                        $query->where('url', '!=', 'seller-subscription');
                    })
                    ->orderBy('header_position')
                    ->get();

                $footerWidget1 = Navigation::query()
                    ->with('page:id,code')
                    ->where('status', true)
                    ->whereJsonContains('type', NavigationType::FooterWidget1->value)
                    ->orderBy('footer_position')
                    ->get();

                $footerPage = $this->resolvePageByCode($locale, $defaultLocale, 'footer');

                return [
                    'data' => [
                        'header' => $this->formatNavigationItems($header, $locale, $defaultLocale),
                        'footer_widget_1' => $this->formatNavigationItems($footerWidget1, $locale, $defaultLocale),
                        'footer_content' => $footerPage
                            ? $this->formatPage($footerPage, Social::orderBy('position')->get())
                            : null,
                    ],
                    'message' => 'Navigation data fetched successfully',
                ];
            }
        );

        return $this->successResponse(
            data: $payload['data'],
            message: $payload['message'],
        );
    }

    private function formatNavigationItems(Collection $items, $lang, $defaultLocale): Collection
    {
        return $items
            ->map(fn(Navigation $item) => $this->formatNavigationItem($item, $lang, $defaultLocale))
            ->values();
    }

    private function formatNavigationItem(Navigation $item, $lang, $defaultLocale): array
    {
        $translations = $this->decodeNavigationTranslations($item->translate);
        return [
            'name' => $item->name,
            'url' => $item->url == '/' ? '/' : ltrim((string) $item->url, '/'),
            'page_code' => $item->page?->code,
            'title' => $translations[$lang] ?? $translations[$defaultLocale] ?? $item->name,
        ];
    }

    private function decodeNavigationTranslations(?string $translations): array
    {
        if (!is_string($translations) || $translations === '') {
            return [];
        }

        $decoded = json_decode($translations, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function rememberCache(string $key, callable $callback, int $minutes = self::CACHE_TTL_MINUTES)
    {
        return Cache::remember($key, now()->addMinutes($minutes), $callback);
    }

    private function cacheKey(string $segment, array $context = []): string
    {
        return implode(':', [
            self::CACHE_PREFIX,
            LandingCache::version(),
            self::THEME,
            $segment,
            md5(json_encode($context)),
        ]);
    }

    private function planCacheState(): string
    {
        return isPlanModuleEnabled() ? 'plan-on' : 'plan-off';
    }
}
