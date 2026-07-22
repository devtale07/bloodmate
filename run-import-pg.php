<?php
/**
 * ONE-TIME DATABASE IMPORT SCRIPT (PostgreSQL version)
 * -----------------------------------------------------
 * Runs each SQL statement individually, correctly handling
 * DO $$ ... $$ blocks (which contain semicolons internally).
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
 * Splits SQL into statements, treating DO $$ ... $$ blocks as a single
 * statement (since they contain semicolons internally).
 */
function splitSqlStatements($sql) {
    $sql = preg_replace('/^--.*$/m', '', $sql);

    $statements = [];
    $buffer = '';
    $inDoBlock = false;

    $lines = explode("\n", $sql);
    foreach ($lines as $line) {
        $buffer .= $line . "\n";

        if (preg_match('/DO\s+\$\$/i', $line)) {
            $inDoBlock = true;
        }

        if ($inDoBlock) {
            if (preg_match('/\$\$\s*;?\s*$/', trim($line)) && strpos($line, 'DO $$') === false) {
                $inDoBlock = false;
                $statements[] = trim($buffer);
                $buffer = '';
            }
            continue;
        }

        if (preg_match('/;\s*$/', trim($line))) {
            $statements[] = trim($buffer);
            $buffer = '';
        }
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
                echo "  FAILED: " . substr(trim($stmt), 0, 80) . "...\n";
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

echo "\nDone. Check above: does 'admin_users' appear?\n";
echo "IMPORTANT: Delete this file from your repo now for security.\n";