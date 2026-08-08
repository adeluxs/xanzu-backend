# Xanzu / MozaPay Wallet, Transfer and RayPlusMoney Fix Report

## Scope

This pass audited and repaired the backend/mobile contracts for wallet bootstrap, Send Money/P2P transfers, transfer configuration and limits, Add Money/top-ups, RayPlusMoney pay-ins, RayPlusMoney payout handling, withdrawal-account ownership, and payment-related performance hot paths.

## Critical fixes

### Send Money

- Fixed transfer-limit enforcement using `$amount` before it was initialized.
- Centralized recipient normalization and removed ambiguous recipient lookups.
- Added client-reference idempotency so retries/double taps cannot create a second transfer.
- Added database row locking and in-transaction balance/limit rechecks.
- Unified preflight and final transfer-limit enforcement.
- Added canonical transfer settings and administrator navigation for Transfer Settings and Transfer Limits.
- Fixed Transfer Settings form section handling so settings actually persist.
- Added editable buyer/merchant transfer limits and sensible unlimited defaults.
- Reduced transfer-usage limit calculations from multiple aggregate queries to one conditional aggregate.
- Added supporting indexes for user phone lookup and transaction usage history.

### Add Money / Top-up

- Fixed upload namespace/validation handling and active-method selection.
- Corrected gateway conversion/rate handling and added invalid-rate/min/max safeguards.
- Kept history/status verification accessible for existing transactions even if starting new deposits is later disabled.
- Removed request-controlled KYC setting overrides.
- Added authenticated payment-status polling to the mobile app.
- Mobile now treats the hosted gateway page as a handoff, then asks the backend for the authoritative provider status before showing success.
- Wallet refresh occurs only after confirmed success.

### RayPlusMoney pay-ins

- Normalized the base URL so `/pay/v01` is never duplicated.
- Uses the hosted redirect pay-in endpoint for generic wallet top-ups.
- Forces/validates XOF for RayPlusMoney deposit methods.
- Persists the provider transaction token.
- Uses the token with the provider confirmation endpoint before crediting the wallet.
- Wallet credit is row-locked and idempotent.
- Return, callback and mobile polling all use the same reconciliation service.
- Callback correlation prefers the signed merchant transaction reference, then provider custom data/token.
- Added HTTP connect/request timeouts and bounded retries.
- Prevented gateway API keys/tokens from being exposed through API serialization or rendered back into admin forms.
- Fixed a trailing-whitespace credential-field-name bug in gateway administration.

### RayPlusMoney payouts / withdrawals

- Mobile/API automatic withdrawals now dispatch RayPlusMoney instead of remaining permanently pending.
- Enforces withdrawal-account ownership.
- Fixed deletion of withdrawal accounts so another user's account cannot be deleted by ID.
- Extracts RayPlusMoney `network` and `customer` from both legacy flat credentials and the application's nested custom-field format.
- Existing malformed RayPlusMoney withdrawal fields are repaired additively to `network` and `customer`.
- Provider rejection refunds the debited wallet exactly once and marks the transaction failed.
- Payout confirmation uses the RayPlusMoney withdrawal confirmation endpoint.
- Daily withdrawal limits are now per-user, ignore failed/cancelled attempts, and `0` means unlimited.
- Notification failures no longer attempt to roll back an already committed wallet debit.

### Performance / resilience

- Cached general settings use the application's settings cache rather than repeated direct queries.
- Common home-screen payload is cached briefly.
- Mobile home resume uses freshness windows instead of forcing duplicate user/home requests every time.
- User/wallet data is retained on transient network errors and cleared only on authoritative authentication failure.
- Send Money recipient lookup performs one normalized backend request instead of several sequential phone-format requests.

## Database migrations added

- `2026_08_05_000001_add_transfer_settings.php`
- `2026_08_05_000003_add_missing_user_columns.php`
- `2026_08_08_120000_repair_transfer_settings_and_rayplusmoney.php`
- `2026_08_08_130000_ensure_default_transfer_limit.php`
- `2026_08_08_140000_optimize_wallet_transfer_queries.php`
- `2026_08_08_150000_fix_rayplus_withdrawal_configuration.php`

All migration changes are additive/data-preserving. The RayPlusMoney repair migration does not overwrite a customized withdrawal field configuration unless it detects the old empty/`1` placeholder configuration.

## RayPlusMoney configuration

Configure the RayPlusMoney gateway with:

- Base URL: `https://app.rayplusmoney.com/pay/v01`
- API Key
- API Token
- Optional default payout network ID

RayPlusMoney wallet top-ups must use an XOF deposit method. For payouts, a withdrawal account should supply a Mobile Money `network` ID and destination `customer` phone number.

## Upgrade steps

1. Back up the database and current source.
2. Deploy the corrected backend source without overwriting `.env` or user uploads.
3. Configure RayPlusMoney credentials in the admin gateway settings or environment.
4. Run `php artisan optimize:clear`.
5. Run `php artisan migrate --force` in production (or `php artisan migrate` locally).
6. Restart queue workers/PHP workers if used.
7. Rebuild and install the corrected mobile application.
8. Test a small transfer and a small RayPlusMoney XOF top-up before increasing transaction amounts.

## Verification performed in this environment

- 1,059 backend PHP files: syntax clean.
- 230 statically discoverable routed controller actions: controller/public method present.
- No duplicated `/pay/v01/pay/v01` RayPlusMoney request construction remains.
- Payment-critical source contains no TODO/FIXME placeholder implementation.
- 400 mobile Dart files inspected; all relative imports resolve.
- 632 literal mobile translation keys checked; none missing.
- 45 referenced mobile route constants checked; none unknown/unregistered.

## Runtime limitations of this verification environment

The supplied backend source does not include `vendor/` and Composer is not installed here, so Laravel migrations/tests/`route:list` could not be executed. Flutter/Dart executables are also unavailable, so `flutter analyze`, `flutter test`, and a device build could not be run. Live RayPlusMoney end-to-end authorization cannot be executed without valid merchant credentials, a public callback URL, and funded test/live accounts. Those runtime checks remain required before production release.
