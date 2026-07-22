<?php
// Database configuration
require_once __DIR__ . '/Config.php';

class Database {
    private $conn;
    private $config;
    private $dbType;

    public function __construct() {
        $this->config = Config::getDatabaseConfig();
        $this->dbType = Config::get('DB_TYPE', 'sqlite');
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            if ($this->dbType === 'sqlite') {
                // SQLite connection
                $dbPath = $this->config['host'] ?? __DIR__ . '/../database/bloodmate.sqlite';
                
                // Create directory if it doesn't exist
                $dbDir = dirname($dbPath);
                if (!is_dir($dbDir)) {
                    mkdir($dbDir, 0755, true);
                }
                
                $this->conn = new PDO("sqlite:" . $dbPath);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                
                // Initialize database if it doesn't exist
                $this->initializeSQLite();
                
            } else {
                // MySQL connection (fallback)
                $dsn = "mysql:host=" . $this->config['host'] . 
                       ";dbname=" . $this->config['dbname'] . 
                       ";port=" . $this->config['port'] . 
                       ";charset=utf8mb4";
                
                $this->conn = new PDO(
                    $dsn,
                    $this->config['username'],
                    $this->config['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::ATTR_PERSISTENT => false
                    ]
                );
            }
        } catch(PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            if (Config::isDebug()) {
                echo "Connection error: " . $exception->getMessage();
            } else {
                echo "Database connection failed. Please try again later.";
            }
        }
        
        return $this->conn;
    }

    /**
     * Initialize SQLite database with schema
     */
    private function initializeSQLite() {
        try {
            // Check if tables exist
            $stmt = $this->conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='admin_users'");
            if ($stmt->fetch()) {
                return; // Database already initialized
            }
            
            // Read and execute SQLite schema
            $schemaFile = __DIR__ . '/../database/bloodmate_sqlite.sql';
            if (file_exists($schemaFile)) {
                $schema = file_get_contents($schemaFile);
                $this->conn->exec($schema);
                error_log("SQLite database initialized successfully");
            } else {
                error_log("SQLite schema file not found: " . $schemaFile);
            }
        } catch (Exception $e) {
            error_log("SQLite initialization error: " . $e->getMessage());
        }
    }

    /**
     * Test database connection
     */
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            if ($conn) {
                $conn->query("SELECT 1");
                return true;
            }
        } catch (Exception $e) {
            error_log("Database test failed: " . $e->getMessage());
        }
        return false;
    }
}
?>