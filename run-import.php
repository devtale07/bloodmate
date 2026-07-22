<?php
/**
 * ONE-TIME DATABASE IMPORT SCRIPT
 * -------------------------------
 * Visit this file once in the browser to import your .sql schema files
 * into the live Aiven MySQL database. DELETE THIS FILE (and remove it
 * from GitHub) immediately after you've successfully run it — leaving
 * it live is a security risk since anyone could re-run it.
 *
 * Place this file at: /run-import.php  (project root)
 * It expects your .sql files to be inside a "database" folder at the
 * project root, e.g. database/bloodmate_schema.sql
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
    // Connect without selecting a database first (dbname may not exist as a
    // schema object on Aiven's shared default db — but usually it's fine to
    // connect directly to defaultdb since Aiven pre-creates it)
    $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
    echo "Connected to MySQL server successfully.\n\n";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Select (or create) the target database
try {
    $dbName = $config['dbname'];
    $pdo->exec("USE `$dbName`");
    echo "Using database: $dbName\n\n";
} catch (PDOException $e) {
    die("Could not select database '{$config['dbname']}': " . $e->getMessage());
}

// List your .sql files here IN ORDER (schema first, then any add-ons)
$sqlFiles = [
    __DIR__ . '/database/bloodmate_schema.sql',
    __DIR__ . '/database/add_users_table.sql',
    __DIR__ . '/database/add_donor_credentials.sql',
];

foreach ($sqlFiles as $file) {
    if (!file_exists($file)) {
        echo "SKIPPED (not found): $file\n";
        continue;
    }

    echo "Importing: " . basename($file) . " ... ";

    $sql = file_get_contents($file);

    try {
        // Use multi-statement execution via PDO's underlying mysqli-style exec
        $pdo->exec($sql);
        echo "OK\n";
    } catch (PDOException $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
    }
}

echo "\nDone. If you saw OK for each file, your schema is imported.\n";
echo "IMPORTANT: Delete this file (run-import.php) from your repo now for security.\n";