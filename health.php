<?php
/**
 * BloodMate Health Check Endpoint
 * Used for monitoring and load balancer health checks
 */

require_once 'config/Config.php';
require_once 'config/Database.php';

header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'version' => '2.0.0',
    'environment' => Config::get('APP_ENV', 'unknown'),
    'checks' => []
];

try {
    // Check database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        $stmt = $db->query("SELECT 1");
        $health['checks']['database'] = [
            'status' => 'healthy',
            'message' => 'Database connection successful'
        ];
    } else {
        $health['checks']['database'] = [
            'status' => 'unhealthy',
            'message' => 'Database connection failed'
        ];
        $health['status'] = 'degraded';
    }
} catch (Exception $e) {
    $health['checks']['database'] = [
        'status' => 'unhealthy',
        'message' => 'Database error: ' . $e->getMessage()
    ];
    $health['status'] = 'degraded';
}

// Check configuration
try {
    $requiredConfigs = ['DB_HOST', 'DB_NAME', 'APP_URL'];
    $missingConfigs = [];
    
    foreach ($requiredConfigs as $config) {
        if (empty(Config::get($config))) {
            $missingConfigs[] = $config;
        }
    }
    
    if (empty($missingConfigs)) {
        $health['checks']['configuration'] = [
            'status' => 'healthy',
            'message' => 'All required configuration present'
        ];
    } else {
        $health['checks']['configuration'] = [
            'status' => 'unhealthy',
            'message' => 'Missing configuration: ' . implode(', ', $missingConfigs)
        ];
        $health['status'] = 'degraded';
    }
} catch (Exception $e) {
    $health['checks']['configuration'] = [
        'status' => 'unhealthy',
        'message' => 'Configuration error: ' . $e->getMessage()
    ];
    $health['status'] = 'degraded';
}

// Check writable directories
$writableDirs = ['logs', 'cache', 'uploads'];
$dirIssues = [];

foreach ($writableDirs as $dir) {
    $dirPath = __DIR__ . '/' . $dir;
    if (!is_dir($dirPath)) {
        $dirIssues[] = "$dir does not exist";
    } elseif (!is_writable($dirPath)) {
        $dirIssues[] = "$dir is not writable";
    }
}

if (empty($dirIssues)) {
    $health['checks']['directories'] = [
        'status' => 'healthy',
        'message' => 'All required directories are writable'
    ];
} else {
    $health['checks']['directories'] = [
        'status' => 'unhealthy',
        'message' => implode(', ', $dirIssues)
    ];
    $health['status'] = 'degraded';
}

// Check PHP version
$phpVersion = phpversion();
if (version_compare($phpVersion, '8.2.0', '>=')) {
    $health['checks']['php'] = [
        'status' => 'healthy',
        'message' => "PHP version $phpVersion (required: 8.2+)"
    ];
} else {
    $health['checks']['php'] = [
        'status' => 'unhealthy',
        'message' => "PHP version $phpVersion (required: 8.2+)"
    ];
    $health['status'] = 'degraded';
}

// Check required PHP extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'curl'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (empty($missingExtensions)) {
    $health['checks']['php_extensions'] = [
        'status' => 'healthy',
        'message' => 'All required PHP extensions loaded'
    ];
} else {
    $health['checks']['php_extensions'] = [
        'status' => 'unhealthy',
        'message' => 'Missing extensions: ' . implode(', ', $missingExtensions)
    ];
    $health['status'] = 'degraded';
}

// Set HTTP status code based on overall health
if ($health['status'] === 'healthy') {
    http_response_code(200);
} else {
    http_response_code(503);
}

echo json_encode($health, JSON_PRETTY_PRINT);
?>
