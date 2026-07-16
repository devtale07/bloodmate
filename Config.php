<?php
/**
 * BloodMate Configuration Manager
 * Loads environment variables and provides configuration access
 */

class Config {
    private static $config = [];
    private static $loaded = false;

    /**
     * Load environment variables from .env file
     */
    private static function loadEnv() {
        if (self::$loaded) return;

        $envFile = __DIR__ . '/../.env';
        
        if (!file_exists($envFile)) {
            // Fallback to .env.example if .env doesn't exist
            $envFile = __DIR__ . '/../.env.example';
        }

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) continue;
                
                // Parse key=value pairs
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    self::$config[trim($key)] = trim($value);
                }
            }
        }
        
        // Set defaults for missing values
        self::setDefaults();
        self::$loaded = true;
    }

    /**
     * Set default values for configuration
     */
    private static function setDefaults() {
        $defaults = [
            'DB_HOST' => 'localhost',
            'DB_NAME' => 'bloodmate',
            'DB_USER' => 'root',
            'DB_PASSWORD' => '',
            'DB_PORT' => '3306',
            'APP_NAME' => 'BloodMate',
            'APP_URL' => 'http://localhost',
            'APP_ENV' => 'development',
            'DEBUG' => 'true',
            'SMTP_HOST' => 'localhost',
            'SMTP_PORT' => '587',
            'SMTP_FROM' => 'noreply@bloodmate.com',
            'SMTP_FROM_NAME' => 'BloodMate',
            'CORS_ALLOWED_ORIGINS' => '*',
            'RATE_LIMIT_REQUESTS' => '100',
            'RATE_LIMIT_PERIOD' => '60',
            'SESSION_LIFETIME' => '7200',
            'SESSION_COOKIE_NAME' => 'bloodmate_session',
        ];

        foreach ($defaults as $key => $value) {
            if (!isset(self::$config[$key])) {
                self::$config[$key] = $value;
            }
        }
    }

    /**
     * Get configuration value
     */
    public static function get($key, $default = null) {
        self::loadEnv();

        // Prefer a real OS environment variable if one is set
        // (e.g. env vars configured in Render's dashboard).
        $envValue = getenv($key);
        if ($envValue !== false && $envValue !== '') {
            return $envValue;
        }

        return self::$config[$key] ?? $default;
    }

    /**
     * Check if application is in debug mode
     */
    public static function isDebug() {
        return self::get('DEBUG', 'false') === 'true';
    }

    /**
     * Get database configuration
     */
    public static function getDatabaseConfig() {
        self::loadEnv();
        return [
            'host' => self::get('DB_HOST'),
            'dbname' => self::get('DB_NAME'),
            'username' => self::get('DB_USER'),
            'password' => self::get('DB_PASSWORD'),
            'port' => self::get('DB_PORT', '3306')
        ];
    }

    /**
     * Get SMTP configuration
     */
    public static function getSMTPConfig() {
        self::loadEnv();
        return [
            'host' => self::get('SMTP_HOST'),
            'port' => self::get('SMTP_PORT'),
            'username' => self::get('SMTP_USER'),
            'password' => self::get('SMTP_PASSWORD'),
            'from' => self::get('SMTP_FROM'),
            'from_name' => self::get('SMTP_FROM_NAME')
        ];
    }

    /**
     * Get allowed CORS origins
     */
    public static function getCORSOrigins() {
        self::loadEnv();
        $origins = self::get('CORS_ALLOWED_ORIGINS', '*');
        return array_map('trim', explode(',', $origins));
    }

    /**
     * Get rate limit configuration
     */
    public static function getRateLimitConfig() {
        self::loadEnv();
        return [
            'requests' => intval(self::get('RATE_LIMIT_REQUESTS', 100)),
            'period' => intval(self::get('RATE_LIMIT_PERIOD', 60))
        ];
    }
}
?>