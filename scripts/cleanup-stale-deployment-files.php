<?php

declare(strict_types=1);

/**
 * Removes files that existed in older Acad/Xanzu/MozaPay deployment archives
 * but are intentionally absent from the current source tree.
 *
 * Why this exists:
 * Extracting a ZIP over an existing deployment does not delete files that were
 * removed/renamed in the new release. Those stale files can break Composer
 * autoload generation even when the current ZIP is correct.
 */

$root = dirname(__DIR__);

// Keep this list explicit and conservative. Never glob/delete application code.
$obsoleteFiles = [
    'app/Models/BnplCheckoutSessoin.php', // misspelled duplicate of BnplCheckoutSession.php
];

$errors = [];
$removed = [];

foreach ($obsoleteFiles as $relativePath) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (! file_exists($path)) {
        continue;
    }

    if (! is_file($path) && ! is_link($path)) {
        $errors[] = "Refusing to remove non-file obsolete path: {$relativePath}";
        continue;
    }

    // For renamed files, only clean the obsolete copy when the canonical file is present.
    if ($relativePath === 'app/Models/BnplCheckoutSessoin.php') {
        $canonical = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Models'
            . DIRECTORY_SEPARATOR . 'BnplCheckoutSession.php';

        if (! is_file($canonical)) {
            $errors[] = 'Cannot remove app/Models/BnplCheckoutSessoin.php because the canonical BnplCheckoutSession.php is missing.';
            continue;
        }
    }

    if (! @unlink($path)) {
        $errors[] = "Could not remove obsolete file {$relativePath}. Check filesystem ownership/permissions.";
        continue;
    }

    $removed[] = $relativePath;
}

if ($errors !== []) {
    fwrite(STDERR, "Stale deployment cleanup failed:\n\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}

if ($removed !== []) {
    fwrite(STDOUT, "Removed stale deployment files:\n- " . implode("\n- ", $removed) . "\n");
} else {
    fwrite(STDOUT, "Stale deployment cleanup: OK (nothing to remove).\n");
}
