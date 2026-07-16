<?php
// Database configuration
require_once __DIR__ . '/Config.php';

class Database {
    private $conn;
    private $config;

    public function __construct() {
        $this->config = Config::getDatabaseConfig();
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
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
