# Database Migration Guide

## Overview

This project has transitioned from manual SQL imports (`xanzu.sql`) to a structured Laravel migration system. **All schema changes must use migrations going forward.**

---

## Current State

| Component | Status |
|---|---|
| `DB/xanzu.sql` | Archived reference only — **do not import into production** |
| `DB/prod_schema.sql` | Production schema snapshot for comparison |
| `DB/compare_schemas.php` | Schema diff tool |
| `DB/SCHEMA_DIFF.md` | Latest schema comparison report |
| `database/migrations/` | Laravel migration files |
| `database/seeders/` | Reference data seeders (countries, settings, permissions, etc.) |

---

## Key Findings

The schema comparison between production and `xanzu.sql` found **no structural differences** requiring migration changes. The only differences are cosmetic:

| Difference | Example | Action |
|---|---|---|
| Character set declarations | `varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci` vs `varchar(255)` | No action needed — table-level charset applies |
| Timestamp function casing | `CURRENT_TIMESTAMP` vs `current_timestamp()` | No action needed — functionally identical |
| JSON storage type | `json` vs `longtext ... CHECK (json_valid(...))` | No action needed — both store JSON data |

**Result:** The existing migrations already match production schema. No new migration files are required for baseline parity.

---

## Workflow: Making Schema Changes

### 1. Create a Migration

```bash
php artisan make:migration add_feature_name_to_table_name --table=table_name
# or for new tables:
php artisan make:migration create_feature_table_name_table
```

### 2. Edit the Migration

Write only the schema change. Never edit a migration that has already been deployed to production.

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('new_feature')->nullable()->after('email');
});
```

### 3. Test Locally

```bash
php artisan migrate
php artisan migrate:rollback
```

Verify both `up()` and `down()` work correctly.

### 4. Commit and Deploy

```bash
git add database/migrations/YYYY_MM_DD_HHMMSS_add_feature_name_to_table_name.php
git commit -m "Add new_feature column to users table"
git push
```

### 5. Run on Production

```bash
php artisan migrate --force
```

---

## Workflow: Seeding Reference Data

### Running Existing Seeders

```bash
php artisan db:seed --class=CountriesSeeder --force
php artisan db:seed --class=SettingsSeeder --force
php artisan db:seed --class=ReferenceDataSeeder --force
```

### Creating New Seeders

Only seed **reference data** (countries, settings, permissions, roles). Never seed user-generated content.

```bash
php artisan make:seeder NewFeatureSeeder
```

Use `updateOrInsert()` to make seeders idempotent:

```php
DB::table('settings')->updateOrInsert(
    ['key' => 'new_feature_status'],
    ['value' => '1', 'type' => 'boolean']
);
```

---

## Safety Procedures

### Before Deploying Migrations

```bash
# 1. Full backup
mysqldump -u root -p xanzu --routines --triggers --single-transaction \
  > /tmp/xanzu_prod_backup_$(date +%Y%m%d_%H%M%S).sql

# 2. Verify backup
mysql -u root -p xanzu < /tmp/xanzu_prod_backup_*.sql

# 3. Check pending migrations
php artisan migrate:status
php artisan migrate:pretend

# 4. Run one migration at a time
php artisan migrate --path=/database/migrations/YYYY_MM_DD_HHMMSS_... --force
php artisan migrate:status
```

### Rollback Procedures

```bash
# Rollback last batch
php artisan migrate:rollback --step=1 --force

# Rollback specific migration
php artisan migrate:rollback --path=/database/migrations/YYYY_MM_DD_HHMMSS_... --force

# Full rollback (nuclear option)
php artisan migrate:reset --force

# Restore from backup if needed
mysql -u root -p xanzu < /tmp/xanzu_prod_backup_YYYYMMDD_HHMMSS.sql
```

### Downtime Prevention

| Migration Type | Safe During Business Hours? |
|---|---|
| New table creation | ✅ Yes |
| Add nullable column | ✅ Yes |
| Add column with default | ✅ Yes (small tables) / ⚠️ No (large tables) |
| Modify column type | ❌ No — use two-phase approach |
| Drop column | ❌ No — maintenance window required |
| Add index | ⚠️ Depends on table size |

---

## Prohibited Actions

- ❌ Modifying `DB/xanzu.sql` and importing into production
- ❌ Running raw SQL on production without a migration file
- ❌ Editing migrations that have already run on production
- ❌ Truncating the `migrations` table
- ❌ Seeding real user/transaction data

---

## Emergency Contacts

Before any production migration:
1. Notify the team via the incident channel
2. Ensure at least one other person has database access
3. Have the backup restoration command ready
4. Schedule during low-traffic windows (02:00–04:00 local time)
