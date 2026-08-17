# Landing Section JSON Safety Fix

## Problem

Landing/page records containing empty, `null`, scalar, or malformed JSON caused
admin Blade views to construct `Illuminate\Support\Fluent` with a non-array.
Laravel then raised `foreach() argument must be of type array|object, null given`
while rendering section management screens such as Hero.

## Fix

- All page and landing-section JSON is normalized to an array before it reaches
  `array_merge()`, `foreach()`, or `Fluent`.
- Active locales inherit missing values from the default/English record.
- Every page-management Blade view has a second defensive array guard.
- Invalid persisted JSON no longer takes the admin page down. A warning named
  `JSON_ARRAY_FALLBACK_USED` records the model, record ID, code, locale, reason,
  value type, and JSON parser error without logging the stored content.
- Landing-section uploads now use Laravel's uploaded-file detection.
- Image removal now deletes the correct stored asset path and refuses paths
  outside the backend `assets` directory.
- Missing section-order input and missing content records now return normal
  validation/404 responses instead of PHP runtime errors.
- Other direct `foreach(json_decode(...))` paths (KYC, withdrawal, gateway,
  template, and notification editors) now use safe iterable fallbacks.

## Deployment

From the Laravel backend directory after replacing the source:

```bash
composer dump-autoload -o
php artisan optimize:clear
php artisan view:clear
php artisan optimize
```

If CyberPanel/OpenLiteSpeed is still serving an old compiled response, restart
LiteSpeed after running the commands above.

## Verification

Open every item under Landing Page Management, especially Hero, Footer, FAQ,
Stats, Testimonials, and multilingual tabs. Invalid legacy data should produce
empty/fallback form values instead of a 500 response. Check the production log
for `JSON_ARRAY_FALLBACK_USED` to identify any database rows that should be
opened and saved again through the admin panel.
