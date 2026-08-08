# Stale deployment file cleanup

When a release ZIP is extracted over an existing server directory, files removed or renamed in the new release are not automatically deleted by most hosting file managers/unzip tools.

This previously left `app/Models/BnplCheckoutSessoin.php` beside the canonical `app/Models/BnplCheckoutSession.php`, causing Composer to report a PSR-4 mismatch and duplicate class declaration.

The project now runs this sequence automatically before Composer regenerates autoload files:

```text
composer deployment-cleanup
composer autoload-lint
```

The cleanup script uses an explicit allow-list and does not use wildcard deletion.

For an existing deployment you may run:

```bash
php scripts/cleanup-stale-deployment-files.php
composer dump-autoload -o
```

Or remove the stale file directly:

```bash
rm -f app/Models/BnplCheckoutSessoin.php
composer dump-autoload -o
```

The canonical model that must remain is:

```text
app/Models/BnplCheckoutSession.php
```
