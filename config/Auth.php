<?php
/**
 * BloodMate Authentication System
 * JWT-based authentication for users and admins
 */

require_once __DIR__ . '/Config.php';

class Auth {
    private static $secret;
    private static $algorithm = 'HS256';
    private static $tokenExpiry = 86400; // 24 hours

    /**
     * Initialize authentication
     */
    private static function init() {
        self::$secret = Config::get('JWT_SECRET', 'default-secret-change-in-production');
    }

    /**
     * Generate JWT token
     */
    public static function generateToken($payload) {
        self::init();
        
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$algorithm]);
        $payload['iat'] = time();
        $payload['exp'] = time() + self::$tokenExpiry;
        
        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::$secret, true);
        $base64UrlSignature = self::base64UrlEncode($signature);
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Verify JWT token
     */
    public static function verifyToken($token) {
        self::init();
        
        if (empty($token)) {
            return false;
        }
        
        $tokenParts = explode('.', $token);
        if (count($tokenParts) !== 3) {
            return false;
        }
        
        list($header, $payload, $signature) = $tokenParts;
        
        // Verify signature
        $expectedSignature = hash_hmac('sha256', $header . "." . $payload, self::$secret, true);
        $expectedSignatureBase64 = self::base64UrlEncode($expectedSignature);
        
        if (!hash_equals($expectedSignatureBase64, $signature)) {
            return false;
        }
        
        // Decode payload
        $decodedPayload = json_decode(self::base64UrlDecode($payload), true);
        
        // Check expiration
        if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
            return false;
        }
        
        return $decodedPayload;
    }

    /**
     * Get current user from token
     */
    public static function getCurrentUser() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
            $payload = self::verifyToken($token);
            
            if ($payload) {
                return $payload;
            }
        }
        
        return null;
    }

    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated() {
        return self::getCurrentUser() !== null;
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        $user = self::getCurrentUser();
        return $user && ($user['role'] === 'admin' || $user['role'] === 'super_admin');
    }

    /**
     * Require authentication (returns 401 if not authenticated)
     */
    public static function requireAuth() {
        if (!self::isAuthenticated()) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Authentication required'
            ]);
            exit;
        }
    }

    /**
     * Require admin role (returns 403 if not admin)
     */
    public static function requireAdmin() {
        self::requireAuth();
        
        if (!self::isAdmin()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Admin access required'
            ]);
            exit;
        }
    }

    /**
     * Hash password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Generate secure random token
     */
    public static function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }

    /**
     * Base64 URL encode
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Login user and return token
     */
    public static function login($username, $password, $db, $table = 'users') {
        try {
            $stmt = $db->prepare("SELECT * FROM $table WHERE username = ? AND is_active = TRUE");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && self::verifyPassword($password, $user['password_hash'])) {
                // Update last login
                $updateStmt = $db->prepare("UPDATE $table SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                // Generate token
                $payload = [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'] ?? 'user'
                ];
                
                $token = self::generateToken($payload);
                
                return [
                    'success' => true,
                    'token' => $token,
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'full_name' => $user['full_name'],
                        'role' => $user['role'] ?? 'user'
                    ]
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Invalid credentials'
            ];
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Login failed'
            ];
        }
    }

    /**
     * Login admin
     */
    public static function loginAdmin($username, $password, $db) {
        try {
            $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ? AND is_active = TRUE");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            
            if ($admin && self::verifyPassword($password, $admin['password_hash'])) {
                // Update last login
                $updateStmt = $db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$admin['id']]);
                
                // Generate token
                $payload = [
                    'user_id' => $admin['id'],
                    'username' => $admin['username'],
                    'email' => $admin['email'],
                    'role' => strtolower($admin['role'])
                ];
                
                $token = self::generateToken($payload);
                
                return [
                    'success' => true,
                    'token' => $token,
                    'admin' => [
                        'id' => $admin['id'],
                        'username' => $admin['username'],
                        'email' => $admin['email'],
                        'full_name' => $admin['full_name'],
                        'role' => strtolower($admin['role'])
                    ]
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Invalid admin credentials'
            ];
        } catch (Exception $e) {
            error_log("Admin login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Admin login failed'
            ];
        }
    }

    /**
     * Logout (client-side token removal)
     */
    public static function logout() {
        // JWT is stateless, logout is handled client-side by removing token
        return [
            'success' => true,
            'message' => 'Logged out successfully'
        ];
    }

    /**
     * Refresh token
     */
    public static function refreshToken($token) {
        $payload = self::verifyToken($token);
        
        if ($payload) {
            // Generate new token with extended expiry
            unset($payload['iat']);
            unset($payload['exp']);
            $newToken = self::generateToken($payload);
            
            return [
                'success' => true,
                'token' => $newToken
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Invalid token'
        ];
    }
}
?>
