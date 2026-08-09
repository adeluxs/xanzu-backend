# Login activity browser/platform schema fix

## Problem

`LoginActivities` historically exposed `browser` and `platform` as computed Eloquent accessors parsed from the stored `agent` user-agent string. The optimized admin dashboard incorrectly attempted to select/group those virtual attributes in MySQL, producing `SQLSTATE[42S22] Unknown column 'browser'` (and the same risk for `platform`).

## Fix

- The dashboard checks whether the physical columns exist before using SQL aggregation.
- Before the migration is run, legacy rows are aggregated from `agent` in bounded chunks, so the dashboard no longer crashes during a rolling deployment.
- A new additive migration adds nullable `browser` and `platform` columns and backfills existing rows by distinct user-agent.
- New login records persist parsed browser/platform values when the columns are available.
- Accessors remain backward-compatible: if a stored value is missing, they still parse it from `agent`.
- The original create-table migration now includes the columns for fresh installations.

## Upgrade

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan cache:clear
```

No existing login activity rows need to be deleted.
