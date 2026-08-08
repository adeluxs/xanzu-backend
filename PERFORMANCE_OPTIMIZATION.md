# Xanzu Backend/Admin + MozaPay Mobile — Full Performance Optimization Audit

Date: 2026-08-08
Baseline:
- `Xanzu-backend-transfer-addmoney-rayplus-fixed.zip`
- `MozaPay-mobile-transfer-addmoney-rayplus-fixed.zip`

## Objective

Audit the complete backend/admin/API and mobile source for avoidable latency, duplicate network work, unbounded data loading, N+1 database access, index-hostile filters, synchronous external-service calls, oversized payloads, unnecessary Flutter rendering work, and cache/memory problems. Preserve the payment/transfer/RayPlus fixes from the baseline.

## Backend / Admin / API changes

### Cross-cutting request performance
- Added API pagination clamping middleware so callers cannot accidentally request huge pages.
- Added request-level database-availability memoization; repeated `setting()`/service-provider calls no longer repeat the same installer DB check.
- Added request-level keyed settings lookup on top of the existing settings cache.
- Added optional slow-query logging controlled by:
  - `PERFORMANCE_LOG_SLOW_QUERIES`
  - `PERFORMANCE_SLOW_QUERY_MS`
- External IP/country detection is cached, skips private/local addresses, and uses short connection/request timeouts with a safe fallback.

### Database/query optimization
- Added an additive application-wide performance-index migration for high-volume transaction, order, listing, notification, chat, ticket, address, deposit/withdrawal, KYC, card, review, analysis, provider/category/brand, and user access patterns.
- Replaced index-hostile `LOWER()`, `DATE()`, `whereDate()` and `whereYear()` usage in high-volume request paths with direct comparisons or bounded datetime ranges where semantically safe.
- Transfer-limit calculations use one conditional aggregate instead of repeated aggregate queries.
- Dashboard charts use grouped SQL rather than hydrating full transaction/order histories into PHP.
- Admin dashboard user/transaction statistics use conditional aggregate queries.
- Merchant dashboard sales/order/product/withdrawal metrics were collapsed from many independent aggregate queries into a small number of conditional aggregates.
- Listing view counts use a direct aggregate instead of `withCount()->pluck()->sum()` over all listings.
- Referral transaction totals use correlated SQL aggregation instead of a query per referral row.
- Product rating distributions use SQL aggregates instead of loading all reviews.
- Checkout/order item resolution batches listings and attributes instead of querying per cart item.
- Waiting-order delivery checks batch required/available inventory queries instead of issuing several queries per order.
- Frontend chat loads a bounded recent window and marks unread messages with one update instead of one write per hydrated message.
- Admin notification total count now uses SQL `COUNT()` rather than `get()->count()`.

### API payload/loading optimization
- Cached stable API metadata: countries, legal pages, languages, registration fields, merchant KYC template, splash/onboarding configuration, withdrawal methods, filter taxonomy, popular/trending taxonomy, provider config, and product section data.
- Added `/product-sections`, combining latest/popular/discounted product sections into one mobile request.
- Product detail no longer loads every approved review; it returns a small preview and SQL rating aggregates.
- Product filtering can omit repeated taxonomy payloads after the first load.
- Support ticket lists/messages use bounded pagination; old messages can be fetched incrementally.
- Scheduled-transfer endpoint now supports bounded pagination rather than an unbounded history load.
- Referral tree traversal is capped to a defensive maximum depth.

### External I/O removed from critical paths
- AI review-summary generation no longer blocks the product-detail response. Cache misses enqueue a bounded background job with duplicate-job locking.
- FCM delivery can run in a queue job and caches the Google OAuth token.
- Normal notification emails are queued when a non-sync queue is configured; OTP/password/security messages remain immediate.
- Bulk subscriber/user mail now dispatches one queue job and reads recipients in chunks rather than hydrating the full audience inside the admin HTTP request.
- External notification/SMS calls use bounded connection/request timeouts and limited retry behavior.

### Bug fixes found during the performance audit
- Ticket API reply now enforces ticket ownership consistently with show/close.
- Duplicate email-verification API route registration removed.
- Stale `/get-coins` route removed because it referenced a nonexistent controller action/service and had no mobile caller.
- `getPlugins()` API action restored and returns only non-secret active plugin metadata.
- SMS producer/consumer payload mismatch (`sms_body` vs `message_body`) fixed.
- Inactive coupon filtering now groups the `OR status=0` condition so it cannot escape the authenticated seller scope.
- Listing analysis relationship required by dashboard/listing metrics restored.

## Mobile performance changes

### Networking
- Central Dio client now coalesces identical concurrent GET requests.
- Safe GET-only retry is limited to connection/time-out/5xx failures; writes are never blindly replayed.
- Endpoint-specific short TTL caches are used for stable/semistable metadata and product/config data.
- Cache is cleared on authenticated writes/token changes.
- In-memory response cache is bounded to 80 LRU-style entries and expired entries are pruned, preventing long browsing sessions from growing memory indefinitely.
- Debug logging no longer serializes full request/response bodies.
- Network connect/send/receive timeouts are bounded.

### Startup/home/dashboard
- Removed artificial splash delay.
- Startup auth/onboarding preference work begins concurrently.
- Authenticated settings/user loads can run in parallel.
- Initial language/settings initializer remains lazy so it does not compete with splash routing.
- Home data keeps existing content during refresh rather than blanking the screen.
- User data has freshness/in-flight coalescing and remains visible through transient network errors.
- Product home sections use one combined backend request, with a backward-compatible parallel fallback.

### Hidden duplicate network work removed
- Wallet, Transactions, Orders, KYC, Product Details, and Cart no longer instantiate `HomeController` simply to read shared user/settings state.
- This prevents unrelated screens from triggering `/home` requests in the background.
- Transfer recipient lookup uses one normalized backend request rather than multiple sequential phone-format requests.
- Transaction type + first-page loading run concurrently.

### Lists/rendering/memory
- Ticket history uses page-based loading instead of `per_page=1000`.
- Ticket message threads use bounded older-message paging and sliver/lazy rendering.
- Review list converted from nested shrink-wrapped list scrolling to sliver/lazy rendering.
- Shared network image widget uses `cached_network_image`, disk caching, and decode-size hints to avoid repeated full-resolution decodes.
- Remaining direct `Image.network`/`NetworkImage` app usages were replaced in meaningful remote-image paths.
- Search debounce reduced and stale overlapping search responses are ignored.
- Filter taxonomy is not re-downloaded on every subsequent filter request.
- Removed artificial reset-password and biometric delays that were not required for correctness.

### Payment/transfer improvements preserved
All fixes from the prior payment pass remain in this source, including:
- idempotent Send Money references and row locking,
- transfer limits/configuration,
- RayPlusMoney base URL normalization,
- verified/idempotent Add Money crediting,
- RayPlus status polling/callback handling,
- mobile-money payout field handling,
- withdrawal-account ownership protection,
- authoritative server-side wallet/limit responses.

## New / important backend files

- `app/Http/Middleware/ClampApiPagination.php`
- `app/Support/Performance/DatabaseAvailability.php`
- `app/Jobs/GenerateListingReviewSummary.php`
- `app/Jobs/SendFcmNotificationJob.php`
- `app/Jobs/SendBulkNotificationJob.php`
- `app/Models/ListingAnalysis.php`
- `database/migrations/2026_08_08_160000_application_wide_performance_indexes.php`

## New / important mobile files

- `lib/presentation/product/model/product_sections_response_model.dart`
- central changes in `lib/backend/dio_client.dart`, `public_api.dart`, `secure_api.dart`
- ticket pagination/rendering changes in `lib/presentation/my_ticket/`
- home/product/network-image/controller dependency changes throughout `lib/presentation/`

## Deployment / upgrade

### Backend

Back up the database first, then from the backend project directory:

```bash
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

A queue worker is now important for non-critical notification/AI work to stay off HTTP requests. Run it under Supervisor/systemd (not a manually attached shell in production), for example:

```bash
php artisan queue:work --queue=notifications,default --sleep=1 --tries=3 --timeout=300
```

For higher traffic, Redis is recommended for cache/queue/session where available. Enable slow-query logging temporarily when profiling:

```env
PERFORMANCE_LOG_SLOW_QUERIES=true
PERFORMANCE_SLOW_QUERY_MS=500
```

Disable it again after profiling if log volume is undesirable.

Also ensure PHP OPcache is enabled in production and serve static/media assets through a web server/CDN rather than PHP where possible.

### Mobile

```bash
flutter pub get
flutter analyze
flutter build appbundle --release
```

For direct APK testing:

```bash
flutter build apk --release
```

## Verification performed in this environment

- Backend PHP syntax: full `app`, `routes`, `database`, and `modules` source pass.
- Routed controller actions: static controller/action validation pass.
- MySQL migration identifier-name preflight: no generated/explicit candidate over 64 characters in the scanned Laravel schema definitions.
- Mobile relative imports: pass.
- Mobile referenced app routes: pass.
- Mobile referenced translation keys: pass.
- Mobile delimiter/structural scan: pass.
- No direct `Image.network`/`NetworkImage` calls remain in the meaningful app source paths audited.
- Generated ZIP archives are integrity-tested after packaging.

## Runtime verification limitation

This execution environment has PHP but does not have the project's Composer-installed `vendor/` tree or a Composer executable, and it does not have Flutter/Dart executables. Therefore `php artisan test`, actual migrations/route cache, `flutter analyze`, and release builds cannot be honestly claimed as executed here. Run the deployment commands above in the real development/staging environment before production release.

## Performance principles intentionally not applied blindly

Not every `get()` or `SingleChildScrollView` was replaced. Small configuration collections, bounded six-item product sections, forms, settings screens, and other naturally small datasets are cheaper and safer left simple. Optimizations were applied where data can grow, network work was duplicated, query plans were index-hostile, external calls blocked requests, or Flutter rendered potentially unbounded collections eagerly.
