<?php
/**
 * ONE-TIME DATABASE IMPORT SCRIPT (v2 - statement by statement)
 * ---------------------------------------------------------------
 * Runs each SQL statement individually so that one "already exists"
 * error doesn't block later statements in the same file (e.g. the
 * admin_users table) from running.
 *
 * DELETE THIS FILE after you've confirmed the import worked.
 */

require_once __DIR__ . '/config/Config.php';

header('Content-Type: text/plain');

$config = Config::getDatabaseConfig();

$dsn = "mysql:host=" . $config['host'] .
       ";port=" . $config['port'] .
       ";charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
];

$sslCa = Config::get('DB_SSL_CA');
if (!empty($sslCa) && file_exists($sslCa)) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
    echo "Connected to MySQL server successfully.\n\n";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

try {
    $dbName = $config['dbname'];
    $pdo->exec("USE `$dbName`");
    echo "Using database: $dbName\n\n";
} catch (PDOException $e) {
    die("Could not select database '{$config['dbname']}': " . $e->getMessage());
}

$sqlFiles = [
    __DIR__ . '/database/bloodmate_schema.sql',
    __DIR__ . '/database/add_users_table.sql',
    __DIR__ . '/database/add_donor_credentials.sql',
];

/**
 * Naive SQL statement splitter — splits on semicolons that end a line,
 * ignoring semicolons inside string literals is NOT handled, but this
 * is fine for standard schema dump files without semicolons in data.
 */
function splitSqlStatements($sql) {
    // Remove comments
    $sql = preg_replace('/^--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($s) { return $s !== ''; }
    );
    return $statements;
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
        try {
            $pdo->exec($stmt);
            $ok++;
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            // These mean the object already exists — safe to ignore
            if (strpos($msg, 'already exists') !== false ||
                strpos($msg, 'Duplicate column name') !== false ||
                strpos($msg, 'Duplicate key name') !== false ||
                strpos($msg, 'Duplicate entry') !== false) {
                $skipped++;
            } else {
                $failed++;
                echo "  FAILED: " . substr($stmt, 0, 80) . "...\n";
                echo "    -> " . $msg . "\n";
            }
        }
    }

    echo "  Done: $ok run, $skipped already existed, $failed real failures\n\n";
}

// Show which tables now exist, so we can confirm admin_users is there
echo "=== Tables now in database ===\n";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    echo "  - $t\n";
}

echo "\nDone. Check above: does 'admin_users' appear in the table list?\n";
echo "IMPORTANT: Delete this file from your repo now for security.\n";