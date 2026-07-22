<?php
require_once __DIR__ . '/config/Config.php';

header('Content-Type: text/plain');

$config = Config::getDatabaseConfig();

echo "=== Config values (exact, with quotes to catch hidden spaces) ===\n";
echo "host: '" . $config['host'] . "'\n";
echo "port: '" . $config['port'] . "'\n";
echo "dbname: '" . $config['dbname'] . "'\n";
echo "username: '" . $config['username'] . "'\n";
echo "password length: " . strlen($config['password']) . " chars\n\n";

$sslCa = Config::get('DB_SSL_CA');
echo "DB_SSL_CA path: '" . $sslCa . "'\n";
echo "DB_SSL_CA file exists: " . (file_exists($sslCa) ? "YES" : "NO") . "\n\n";

$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
if (!empty($sslCa) && file_exists($sslCa)) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}

// Method A: dbname embedded in DSN (like fix-admin.php did)
echo "=== Method A: dbname in DSN ===\n";
try {
    $dsnA = "mysql:host=" . $config['host'] . ";port=" . $config['port'] . ";dbname=" . $config['dbname'] . ";charset=utf8mb4";
    $pdoA = new PDO($dsnA, $config['username'], $config['password'], $options);
    echo "Connected OK.\n";
    echo "SELECT DATABASE(): " . $pdoA->query("SELECT DATABASE()")->fetchColumn() . "\n";
    $tables = $pdoA->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables (" . count($tables) . "): " . implode(', ', $tables) . "\n";
} catch (PDOException $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}

echo "\n";

// Method B: no dbname in DSN, then USE (like run-import-v2.php did)
echo "=== Method B: USE after connecting ===\n";
try {
    $dsnB = "mysql:host=" . $config['host'] . ";port=" . $config['port'] . ";charset=utf8mb4";
    $pdoB = new PDO($dsnB, $config['username'], $config['password'], $options);
    echo "Connected OK.\n";
    $pdoB->exec("USE `" . $config['dbname'] . "`");
    echo "SELECT DATABASE(): " . $pdoB->query("SELECT DATABASE()")->fetchColumn() . "\n";
    $tables = $pdoB->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables (" . count($tables) . "): " . implode(', ', $tables) . "\n";
} catch (PDOException $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}