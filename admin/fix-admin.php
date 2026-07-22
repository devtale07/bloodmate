<?php
/**
 * ADMIN USER CHECK & FIX SCRIPT
 * -----------------------------
 * Checks if the 'admin' user exists in admin_users, and if not (or if
 * you want to reset the password), creates/updates it with a fresh
 * password hash for 'admin123'.
 *
 * DELETE THIS FILE after you've confirmed login works.
 */

require_once __DIR__ . '/config/Config.php';

header('Content-Type: text/plain');

$config = Config::getDatabaseConfig();

$dsn = "mysql:host=" . $config['host'] .
       ";port=" . $config['port'] .
       ";dbname=" . $config['dbname'] .
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
    echo "Connected.\n\n";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Show the admin_users table structure first, so we know the real column names
echo "=== admin_users columns ===\n";
$cols = $pdo->query("SHOW COLUMNS FROM admin_users")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "  - {$c['Field']} ({$c['Type']})\n";
}
echo "\n";

// Check existing rows (don't print the actual hash, just whether one exists)
echo "=== Existing rows in admin_users ===\n";
$rows = $pdo->query("SELECT id, username, is_active FROM admin_users")->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) {
    echo "  (no rows found)\n";
} else {
    foreach ($rows as $r) {
        echo "  - id={$r['id']} username={$r['username']} is_active={$r['is_active']}\n";
    }
}
echo "\n";

// Now create or reset the 'admin' user with a fresh hash for 'admin123'
$newHash = password_hash('admin123', PASSWORD_DEFAULT);

$stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = 'admin'");
$stmt->execute();
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    $upd = $pdo->prepare("UPDATE admin_users SET password_hash = ?, is_active = 1 WHERE username = 'admin'");
    $upd->execute([$newHash]);
    echo "UPDATED existing 'admin' user (id={$existing['id']}) with a fresh password hash for 'admin123' and set is_active=1.\n";
} else {
    // Try a reasonably safe insert; adjust columns if your table requires more
    try {
        $ins = $pdo->prepare("
            INSERT INTO admin_users (username, password_hash, email, full_name, role, is_active)
            VALUES ('admin', ?, 'admin@bloodmate.local', 'Administrator', 'admin', 1)
        ");
        $ins->execute([$newHash]);
        echo "CREATED new 'admin' user with password 'admin123'.\n";
    } catch (PDOException $e) {
        echo "INSERT FAILED: " . $e->getMessage() . "\n";
        echo "This likely means admin_users has required columns not included above.\n";
        echo "Paste the column list above back to me and I'll fix the insert.\n";
    }
}

echo "\nDone. Try logging in now with admin / admin123.\n";
echo "IMPORTANT: Delete this file from your repo now for security.\n";