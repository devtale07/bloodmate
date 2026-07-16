<?php
/**
 * BloodMate Security Middleware
 * Handles security headers, CORS, and security-related functions
 */

require_once __DIR__ . '/Config.php';

class Security {
    
    /**
     * Set security headers for all responses
     */
    public static function setSecurityHeaders() {
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy (basic version)
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; " .
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; " .
               "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self'; " .
               "frame-ancestors 'self';";
        header("Content-Security-Policy: $csp");
        
        // HSTS (only in production with HTTPS)
        if (Config::get('APP_ENV') === 'production' && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // Remove server information
        header_remove('Server');
    }

    /**
     * Set CORS headers based on configuration
     */
    public static function setCorsHeaders() {
        $origins = Config::getCORSOrigins();
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        
        if (in_array('*', $origins) || in_array($origin, $origins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        }
        
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    /**
     * Sanitize input data to prevent XSS attacks
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        
        return $data;
    }

    /**
     * Validate and sanitize email
     */
    public static function validateEmail($email) {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Validate phone number (basic validation)
     */
    public static function validatePhone($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Check if it's a valid length (10-15 digits)
        return strlen($phone) >= 10 && strlen($phone) <= 15;
    }

    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token
     */
    public static function validateCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Rate limiting implementation
     */
    public static function rateLimit($identifier = null, $requests = null, $period = null) {
        if ($identifier === null) {
            $identifier = self::getClientIdentifier();
        }
        
        $config = Config::getRateLimitConfig();
        $requests = $requests ?? $config['requests'];
        $period = $period ?? $config['period'];
        
        $rateLimitFile = __DIR__ . '/../cache/rate_limit_' . md5($identifier) . '.json';
        $cacheDir = dirname($rateLimitFile);
        
        // Create cache directory if it doesn't exist
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $data = [];
        if (file_exists($rateLimitFile)) {
            $data = json_decode(file_get_contents($rateLimitFile), true) ?: [];
        }
        
        $currentTime = time();
        $windowStart = $currentTime - $period;
        
        // Clean old entries
        $data = array_filter($data, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        // Check if limit exceeded
        if (count($data) >= $requests) {
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $period
            ]);
            exit;
        }
        
        // Add current request
        $data[] = $currentTime;
        file_put_contents($rateLimitFile, json_encode($data));
        
        return true;
    }

    /**
     * Get client identifier for rate limiting
     */
    private static function getClientIdentifier() {
        $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // If behind proxy, use X-Forwarded-For
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $identifier = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        
        return $identifier;
    }

    /**
     * Validate JSON input
     */
    public static function getJsonInput() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid JSON input'
            ]);
            exit;
        }
        
        return $data ?: [];
    }

    /**
     * Log security events
     */
    public static function logSecurityEvent($event, $details = []) {
        $logFile = __DIR__ . '/../logs/security.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $logEntry = sprintf(
            "[%s] IP: %s | Event: %s | User-Agent: %s | Details: %s\n",
            $timestamp,
            $ip,
            $event,
            $userAgent,
            json_encode($details)
        );
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Check if request is secure (HTTPS)
     */
    public static function isSecureRequest() {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               ($_SERVER['SERVER_PORT'] == 443) ||
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }

    /**
     * Enforce HTTPS in production
     */
    public static function enforceHttps() {
        if (Config::get('APP_ENV') === 'production' && !self::isSecureRequest()) {
            $httpsUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header('Location: ' . $httpsUrl);
            exit;
        }
    }
}
?>
