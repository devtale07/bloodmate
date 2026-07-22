<?php
require_once 'middleware.php';

// Apply rate limiting for login attempts
Security::rateLimit(null, 5, 300); // 5 attempts per 5 minutes

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Method not allowed', null, 405);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        sendJsonResponse(false, 'Database connection failed', null, 500);
    }
    
    // Get and sanitize input
    $input = Security::getJsonInput();
    $input = sanitizeInputData($input);
    
    // Validate required fields
    validateRequiredFields($input, ['username', 'password']);
    
    // Get user
    $stmt = $db->prepare("
        SELECT id, username, password_hash, email, full_name, phone, is_active, is_verified 
        FROM users 
        WHERE username = ? AND is_active = 1
    ");
    $stmt->execute([$input['username']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        Security::logSecurityEvent('user_login_failed', ['username' => $input['username']]);
        sendJsonResponse(false, 'Invalid credentials', null, 401);
    }
    
    // Verify password
    if (!Auth::verifyPassword($input['password'], $user['password_hash'])) {
        Security::logSecurityEvent('user_login_failed', ['username' => $input['username'], 'user_id' => $user['id']]);
        sendJsonResponse(false, 'Invalid credentials', null, 401);
    }
    
    // Generate JWT token
    $payload = [
        'user_id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => 'user'
    ];
    $token = Auth::generateToken($payload);
    
    // Update last login
    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Log the login
    Logger::info('User login successful', ['username' => $user['username']]);
    Security::logSecurityEvent('user_login_success', ['user_id' => $user['id'], 'username' => $user['username']]);
    
    sendJsonResponse(true, 'Login successful', [
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'phone' => $user['phone']
        ]
    ]);
    
} catch (PDOException $e) {
    Logger::error("Database error in login-user.php: " . $e->getMessage());
    sendJsonResponse(false, 'Database error occurred', null, 500);
} catch (Exception $e) {
    Logger::error("Error in login-user.php: " . $e->getMessage());
    sendJsonResponse(false, 'An error occurred', null, 500);
}
?>
