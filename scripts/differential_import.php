<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$dryRun = in_array('--dry-run', $argv, true);
$forceUserData = in_array('--force-user-data', $argv, true);
$skipAnalytics = in_array('--skip-analytics', $argv, true);

$sqlFile = null;

foreach ($argv as $index => $arg) {
    if ($arg === '--sql' && isset($argv[$index + 1])) {
        $sqlFile = $argv[$index + 1];
        break;
    }
}

if ($sqlFile === null) {
    $candidates = [
        __DIR__ . '/../DB/xanzu.sql',
        __DIR__ . '/../../DB/xanzu.sql',
        __DIR__ . '/../backend-xanzu/DB/xanzu.sql',
        getcwd() . '/DB/xanzu.sql',
        getcwd() . '/backend-xanzu/DB/xanzu.sql',
    ];

    foreach ($candidates as $candidate) {
        if (file_exists($candidate)) {
            $sqlFile = $candidate;
            break;
        }
    }
}

if ($sqlFile === null || !file_exists($sqlFile)) {
    fwrite(STDERR, "SQL file not found. Use --sql /path/to/xanzu.sql to specify it.\n");
    exit(1);
}

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $_ENV['DB_HOST'] ?? '127.0.0.1',
    $_ENV['DB_PORT'] ?? '3306',
    $_ENV['DB_DATABASE'] ?? 'xanzu'
);

$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, 'Database connection failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$rawSql = file_get_contents($sqlFile);
if ($rawSql === false) {
    fwrite(STDERR, "Failed to read SQL file: $sqlFile\n");
    exit(1);
}

$statements = parseSqlStatements($rawSql);
$schemaChanges = [];
$dataChanges = [];
$skipped = [];
$errors = [];

$userSensitiveTables = [
    'users',
    'transactions',
    'orders',
    'order_items',
    'withdraw_accounts',
    'withdrawal_schedules',
    'user_kycs',
    'kycs',
    'login_activities',
    'messages',
    'message_attachments',
    'chats',
    'chat_attachments',
    'shipping_addresses',
    'favorite_seller',
    'followers',
    'recent_searches',
    'user_devices',
    'user_credit_limit_histories',
    'listing_analysis',
    'phone_otps',
    'personal_access_tokens',
    'password_reset_tokens',
    'password_resets',
    'failed_jobs',
    'jobs',
];

if ($forceUserData) {
    $userSensitiveTables = [];
}

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if ($stmt === '') {
        continue;
    }

    if (stripos($stmt, 'CREATE TABLE') === 0) {
        $tableName = extractTableName($stmt);
        if ($tableName === null) {
            $errors[] = "Could not extract table name from CREATE TABLE: " . substr($stmt, 0, 100);
            continue;
        }

        if (in_array($tableName, $userSensitiveTables, true)) {
            $skipped[] = "SKIPPED (user-sensitive) CREATE TABLE: `$tableName`";
            continue;
        }

        $exists = tableExists($pdo, $tableName);
        if (!$exists) {
            $schemaChanges[] = "CREATE TABLE IF NOT EXISTS `$tableName` " . extractTableBody($stmt);
        } else {
            $newColumns = getNewColumns($pdo, $tableName, $stmt);
            if ($newColumns !== []) {
                foreach ($newColumns as $colDef) {
                    $schemaChanges[] = "ALTER TABLE `$tableName` ADD COLUMN " . trim($colDef) . ";";
                }
            }
        }
    } elseif (stripos($stmt, 'INSERT INTO') === 0) {
        $tableName = extractTableName($stmt);
        if ($tableName === null) {
            $errors[] = "Could not extract table name from INSERT: " . substr($stmt, 0, 100);
            continue;
        }

        if (in_array($tableName, $userSensitiveTables, true)) {
            $skipped[] = "SKIPPED (user-sensitive) INSERT INTO: `$tableName`";
            continue;
        }

        if ($skipAnalytics && $tableName === 'listing_analysis') {
            $skipped[] = "SKIPPED (analytics) INSERT INTO: `$tableName`";
            continue;
        }

        $upsertStmt = convertToUpsert($pdo, $stmt, $tableName);
        if ($upsertStmt !== null) {
            $dataChanges[] = $upsertStmt;
        }
    }
}

if ($dryRun) {
    echo "=== DRY RUN MODE ===\n\n";
    echo "Schema changes (" . count($schemaChanges) . "):\n";
    foreach ($schemaChanges as $change) {
        echo "  + " . $change . "\n";
    }
    echo "\nData changes (" . count($dataChanges) . "):\n";
    foreach (array_slice($dataChanges, 0, 20) as $change) {
        echo "  + " . substr($change, 0, 120) . (strlen($change) > 120 ? '...' : '') . "\n";
    }
    if (count($dataChanges) > 20) {
        echo "  ... and " . (count($dataChanges) - 20) . " more\n";
    }
    echo "\nSkipped (" . count($skipped) . "):\n";
    foreach ($skipped as $s) {
        echo "  - " . $s . "\n";
    }
    if ($errors !== []) {
        echo "\nErrors (" . count($errors) . "):\n";
        foreach ($errors as $e) {
            echo "  ! " . $e . "\n";
        }
    }
    exit(0);
}

if ($schemaChanges === [] && $dataChanges === []) {
    echo "No changes to apply. Database is already up to date.\n";
    exit(0);
}

echo "Applying " . count($schemaChanges) . " schema changes and " . count($dataChanges) . " data changes...\n";

$pdo->beginTransaction();

try {
    foreach ($schemaChanges as $change) {
        $pdo->exec($change);
    }
    foreach ($dataChanges as $change) {
        $pdo->exec($change);
    }
    $pdo->commit();
    echo "Differential import completed successfully.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Import failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

function parseSqlStatements(string $sql): array
{
    $statements = [];
    $current = '';
    $inString = false;
    $stringChar = '';

    $len = strlen($sql);
    for ($i = 0; $i < $len; $i++) {
        $char = $sql[$i];

        if ($inString) {
            $current .= $char;
            if ($char === '\\' && $i + 1 < $len) {
                $i++;
                $current .= $sql[$i];
            } elseif ($char === $stringChar) {
                $inString = false;
            }
        } elseif ($char === "'" || $char === '"') {
            $inString = true;
            $stringChar = $char;
            $current .= $char;
        } elseif ($char === ';') {
            $statements[] = $current;
            $current = '';
        } elseif ($char === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
            while ($i < $len && $sql[$i] !== "\n") {
                $i++;
            }
        } elseif ($char === '/' && $i + 1 < $len && $sql[$i + 1] === '*') {
            while ($i < $len - 1 && !($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                $i++;
            }
            $i++;
        } else {
            $current .= $char;
        }
    }

    if (trim($current) !== '') {
        $statements[] = $current;
    }

    return $statements;
}

function extractTableName(string $stmt): ?string
{
    if (preg_match('/CREATE TABLE\s+`([^`]+)`/i', $stmt, $matches)) {
        return $matches[1];
    }
    if (preg_match('/INSERT INTO\s+`([^`]+)`/i', $stmt, $matches)) {
        return $matches[1];
    }
    return null;
}

function extractTableBody(string $stmt): string
{
    if (preg_match('/CREATE TABLE\s+`[^`]+`\s*\((.+)\)\s*;/is', $stmt, $matches)) {
        return '(' . $matches[1] . ')';
    }
    return $stmt;
}

function tableExists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->execute([$tableName]);
    return (int) $stmt->fetchColumn() > 0;
}

function getNewColumns(PDO $pdo, string $tableName, string $createStmt): array
{
    if (!preg_match('/CREATE TABLE\s+`[^`]+`\s*\((.+)\)\s*;/is', $createStmt, $matches)) {
        return [];
    }

    $columnsBlock = $matches[1];
    $newColumns = [];

    $existingColumns = [];
    $stmt = $pdo->prepare('SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->execute([$tableName]);
    while ($row = $stmt->fetch()) {
        $existingColumns[] = $row['column_name'];
    }

    if (preg_match_all('/`([^`]+)`\s+([^,]+(?:\([^)]*\))?[^,]*)/', $columnsBlock, $columnMatches, PREG_SET_ORDER)) {
        foreach ($columnMatches as $match) {
            $colName = $match[1];
            $colDef = trim($match[2]);
            if (!in_array($colName, $existingColumns, true) && strtolower($colName) !== 'id') {
                $newColumns[] = "`$colName` $colDef";
            }
        }
    }

    return $newColumns;
}

function convertToUpsert(PDO $pdo, string $stmt, string $tableName): ?string
{
    if (!preg_match('/INSERT INTO\s+`[^`]+`\s*\(([^)]+)\)\s*VALUES\s*\((.+)\)\s*$/is', $stmt, $matches)) {
        return null;
    }

    $columnsPart = trim($matches[1]);
    $valuesPart = trim($matches[2]);

    $primaryKey = getPrimaryKey($pdo, $tableName);
    if ($primaryKey === null) {
        return "INSERT IGNORE INTO `$tableName` ($columnsPart) VALUES ($valuesPart);";
    }

    $uniqueColumns = getUniqueKeys($pdo, $tableName);
    $updateColumns = [];

    $colNames = array_map('trim', explode(',', str_replace('`', '', $columnsPart)));
    foreach ($colNames as $col) {
        if ($col === $primaryKey || in_array($col, $uniqueColumns, true)) {
            continue;
        }
        $updateColumns[] = "`$col` = VALUES(`$col`)";
    }

    if ($updateColumns === []) {
        return "INSERT IGNORE INTO `$tableName` ($columnsPart) VALUES ($valuesPart);";
    }

    $updateClause = implode(', ', $updateColumns);
    return "INSERT INTO `$tableName` ($columnsPart) VALUES ($valuesPart) ON DUPLICATE KEY UPDATE $updateClause;";
}

function getPrimaryKey(PDO $pdo, string $tableName): ?string
{
    $stmt = $pdo->prepare('SELECT column_name FROM information_schema.key_column_usage WHERE table_schema = DATABASE() AND table_name = ? AND constraint_name = \'PRIMARY\'');
    $stmt->execute([$tableName]);
    $col = $stmt->fetchColumn();
    return $col !== false ? $col : null;
}

function getUniqueKeys(PDO $pdo, string $tableName): array
{
    $stmt = $pdo->prepare('SELECT column_name FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND non_unique = 0 AND index_name != \'PRIMARY\'');
    $stmt->execute([$tableName]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
