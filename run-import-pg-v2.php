<?php
/**
 * ONE-TIME DATABASE IMPORT SCRIPT (PostgreSQL version) - v2
 * -----------------------------------------------------------
 * Uses a character-level scanner to correctly split SQL statements,
 * properly handling $$ ... $$ dollar-quoted blocks (used by both
 * DO blocks and CREATE FUNCTION bodies) and single-quoted strings.
 *
 * DELETE THIS FILE after you've confirmed the import worked.
 */

require_once __DIR__ . '/config/Config.php';

header('Content-Type: text/plain');

$config = Config::getDatabaseConfig();

$dsn = "pgsql:host=" . $config['host'] .
       ";port=" . $config['port'] .
       ";dbname=" . $config['dbname'];

$sslMode = Config::get('DB_SSLMODE');
if (!empty($sslMode)) {
    $dsn .= ";sslmode=" . $sslMode;
}

$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
    echo "Connected to Postgres successfully.\n";
    echo "Current database: " . $pdo->query("SELECT current_database()")->fetchColumn() . "\n\n";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$sqlFiles = [
    __DIR__ . '/database/bloodmate_schema.sql',
    __DIR__ . '/database/add_users_table.sql',
    __DIR__ . '/database/add_donor_credentials.sql',
];

/**
 * Character-level SQL statement splitter.
 * Correctly handles $$ ... $$ dollar-quoted blocks and '...' string
 * literals so semicolons inside them don't split the statement.
 */
function splitSqlStatements($sql) {
    // Strip line comments first (safe: -- never appears inside our strings)
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);

    $statements = [];
    $buffer = '';
    $inDollar = false;
    $inSingleQuote = false;
    $len = strlen($sql);
    $i = 0;

    while ($i < $len) {
        // Detect $$ (only toggle when not inside a single-quoted string)
        if (!$inSingleQuote && $i + 1 < $len && $sql[$i] === '$' && $sql[$i+1] === '$') {
            $inDollar = !$inDollar;
            $buffer .= '$$';
            $i += 2;
            continue;
        }

        $ch = $sql[$i];

        if (!$inDollar && $ch === "'") {
            $inSingleQuote = !$inSingleQuote;
        }

        if ($ch === ';' && !$inDollar && !$inSingleQuote) {
            $buffer .= ';';
            $statements[] = trim($buffer);
            $buffer = '';
            $i++;
            continue;
        }

        $buffer .= $ch;
        $i++;
    }

    if (trim($buffer) !== '') {
        $statements[] = trim($buffer);
    }

    return array_filter($statements, function($s) { return trim($s) !== ''; });
}

foreach ($sqlFiles as $file) {
    if (!file_exists($file)) {
        echo "SKIPPED (not found): " . basename($file) . "\n";
        continue;
    }

    echo "=== " . basename($file) . " ===\n";
    $sql = file_get_contents($file);
    $statements = splitSqlStatements($sql);

    $ok = 0; $skipped = 0; $failed = 0;

    foreach ($statements as $stmt) {
        if (trim($stmt) === '') continue;
        try {
            $pdo->exec($stmt);
            $ok++;
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'already exists') !== false ||
                strpos($msg, 'duplicate key') !== false) {
                $skipped++;
            } else {
                $failed++;
                echo "  FAILED: " . substr(trim($stmt), 0, 100) . "...\n";
                echo "    -> " . $msg . "\n";
            }
        }
    }

    echo "  Done: $ok run, $skipped already existed, $failed real failures\n\n";
}

echo "=== Tables now in database ===\n";
$tables = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    echo "  - $t\n";
}

echo "\n=== Views now in database ===\n";
$views = $pdo->query("SELECT viewname FROM pg_views WHERE schemaname = 'public'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($views as $v) {
    echo "  - $v\n";
}

echo "\n=== Functions now in database ===\n";
$funcs = $pdo->query("SELECT proname FROM pg_proc p JOIN pg_namespace n ON p.pronamespace = n.oid WHERE n.nspname = 'public'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($funcs as $f) {
    echo "  - $f\n";
}

echo "\nDone. Check above: does 'admin_users' appear in Tables?\n";
echo "IMPORTANT: Delete this file from your repo now for security.\n";