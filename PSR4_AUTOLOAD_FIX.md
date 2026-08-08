# PSR-4 / Composer Autoload Fix

This source includes the Composer optimized-autoload repair applied after the warning:

`App\Models\BnplCheckoutSession located in ./app/Models/BnplCheckoutSessoin.php does not comply with psr-4 autoloading standard.`

## Corrections

- Removed the misspelled duplicate `app/Models/BnplCheckoutSessoin.php`.
- Preserved the authoritative `app/Models/BnplCheckoutSession.php` model.
- Added `scripts/check-psr4.php` to validate project PSR-4 class/interface/trait/enum declarations.
- Added Composer script `autoload-lint`.
- Added `pre-autoload-dump` PSR-4 validation so future path/class mismatches fail early.
- Excluded module test directories from the production `modules/*` classmap. This prevents copied package test classes from being considered production autoload classes.

## Validation performed

- PSR-4 declarations checked: 340
- Production autoload/classmap declarations checked for duplicates: 736
- Project-owned PHP files syntax checked: 1,060
- `BnplCheckoutSessoin` references remaining: 0
- Case-insensitive PSR-4 path collisions: 0

## Recommended command after updating

```bash
composer dump-autoload -o
```

The PSR-4 preflight runs automatically before Composer generates the autoloader. It can also be run manually:

```bash
composer autoload-lint
# or
php scripts/check-psr4.php
```
