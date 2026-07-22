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
    
    // Get admin user
    $stmt = $db->prepare("
        SELECT id, username, password_hash, email, full_name, role, is_active 
        FROM admin_users 
        WHERE username = ? AND is_active = TRUE
    ");
    $stmt->execute([$input['username']]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        Security::logSecurityEvent('admin_login_failed', ['username' => $input['username']]);
        sendJsonResponse(false, 'Invalid credentials', null, 401);
    }
    
    // Verify password
    if (!Auth::verifyPassword($input['password'], $admin['password_hash'])) {
        Security::logSecurityEvent('admin_login_failed', ['username' => $input['username'], 'admin_id' => $admin['id']]);
        sendJsonResponse(false, 'Invalid credentials', null, 401);
    }
    
    // Generate JWT token
    $payload = [
        'user_id' => $admin['id'],
        'username' => $admin['username'],
        'email' => $admin['email'],
        'role' => strtolower($admin['role'])
    ];
    $token = Auth::generateToken($payload);
    
    // Update last login
    $stmt = $db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$admin['id']]);
    
    // Log the login
    Logger::info('Admin login successful', ['username' => $admin['username'], 'role' => $admin['role']]);
    Security::logSecurityEvent('admin_login_success', ['admin_id' => $admin['id'], 'username' => $admin['username']]);
    
    sendJsonResponse(true, 'Login successful', [
        'token' => $token,
        'user' => [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'full_name' => $admin['full_name'],
            'role' => strtolower($admin['role'])
        ]
    ]);
    
} catch (PDOException $e) {
    Logger::error("Database error in admin-login.php: " . $e->getMessage());
    sendJsonResponse(false, 'Database error occurred', null, 500);
} catch (Exception $e) {
    Logger::error("Error in admin-login.php: " . $e->getMessage());
    sendJsonResponse(false, 'An error occurred', null, 500);
}
?>