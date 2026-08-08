<?php

declare(strict_types=1);

/**
 * Composer PSR-4 preflight for project-owned namespaces.
 *
 * It detects:
 * - class/interface/trait/enum declarations whose file path does not match PSR-4;
 * - duplicate fully-qualified declarations across PSR-4 roots.
 *
 * This script intentionally requires no Composer autoloader so it can run before
 * optimized autoload files are generated.
 */

$projectRoot = dirname(__DIR__);
$roots = [
    ['dir' => $projectRoot.'/app', 'prefix' => 'App\\'],
    ['dir' => $projectRoot.'/database/factories', 'prefix' => 'Database\\Factories\\'],
    ['dir' => $projectRoot.'/database/seeders', 'prefix' => 'Database\\Seeders\\'],
    ['dir' => $projectRoot.'/tests', 'prefix' => 'Tests\\'],
];

/** @return list<string> */
function psr4Declarations(string $file): array
{
    $tokens = token_get_all((string) file_get_contents($file));
    $namespace = '';
    $declarations = [];
    $depth = 0;
    $count = count($tokens);

    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];

        if (is_string($token)) {
            if ($token === '{') {
                $depth++;
            } elseif ($token === '}') {
                $depth = max(0, $depth - 1);
            }
            continue;
        }

        if ($depth === 0 && $token[0] === T_NAMESPACE) {
            $namespaceBuffer = '';
            for ($j = $i + 1; $j < $count; $j++) {
                $next = $tokens[$j];
                if (is_string($next) && ($next === ';' || $next === '{')) {
                    $i = $j;
                    if ($next === '{') {
                        $depth++;
                    }
                    break;
                }

                $namespaceTokens = [T_STRING, T_NS_SEPARATOR];
                if (defined('T_NAME_QUALIFIED')) {
                    $namespaceTokens[] = T_NAME_QUALIFIED;
                }

                if (is_array($next) && in_array($next[0], $namespaceTokens, true)) {
                    $namespaceBuffer .= $next[1];
                }
            }
            $namespace = trim($namespaceBuffer, '\\');
            continue;
        }

        $declarationTokens = [T_CLASS, T_INTERFACE, T_TRAIT];
        if (defined('T_ENUM')) {
            $declarationTokens[] = T_ENUM;
        }

        if ($depth !== 0 || ! in_array($token[0], $declarationTokens, true)) {
            continue;
        }

        for ($j = $i + 1; $j < $count; $j++) {
            $next = $tokens[$j];
            if (is_array($next) && $next[0] === T_STRING) {
                $declarations[] = ltrim(($namespace !== '' ? $namespace.'\\' : '').$next[1], '\\');
                break;
            }
            if (is_string($next) && ($next === '{' || $next === ';')) {
                break;
            }
        }
    }

    return $declarations;
}

$errors = [];
$seen = [];
$checked = 0;

foreach ($roots as $root) {
    if (! is_dir($root['dir'])) {
        continue;
    }

    $base = str_replace('\\', '/', rtrim($root['dir'], '/\\'));
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root['dir'], FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (! $file->isFile() || strtolower($file->getExtension()) !== 'php') {
            continue;
        }

        $path = str_replace('\\', '/', $file->getPathname());
        $relative = substr($path, strlen($base) + 1);
        $expected = $root['prefix'].str_replace('/', '\\', substr($relative, 0, -4));

        foreach (psr4Declarations($path) as $fqcn) {
            $checked++;
            $key = strtolower($fqcn);
            $seen[$key][] = ['fqcn' => $fqcn, 'path' => $path];

            if ($fqcn !== $expected) {
                $errors[] = "PSR-4 mismatch: {$fqcn} is declared in {$path}; expected {$expected}.";
            }
        }
    }
}

foreach ($seen as $declarations) {
    $paths = array_values(array_unique(array_column($declarations, 'path')));
    if (count($paths) > 1) {
        $errors[] = 'Duplicate declaration: '.implode(' / ', array_map(
            static fn (array $declaration): string => $declaration['fqcn'].' @ '.$declaration['path'],
            $declarations
        ));
    }
}

if ($errors !== []) {
    fwrite(STDERR, "PSR-4 preflight failed:\n - ".implode("\n - ", $errors)."\n");
    exit(1);
}

echo "PSR-4 preflight: OK ({$checked} declarations checked).\n";
