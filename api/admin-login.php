<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Validate required fields
    if (empty($_POST['username']) || empty($_POST['password'])) {
        echo json_encode(['success' => false, 'message' => 'Username and password are required']);
        exit;
    }
    
    // Get admin user
    $stmt = $db->prepare("
        SELECT id, username, password_hash, email, full_name, role, is_active 
        FROM admin_users 
        WHERE username = ? AND is_active = 1
    ");
    $stmt->execute([$_POST['username']]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit;
    }
    
    // Verify password
    if (!password_verify($_POST['password'], $admin['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit;
    }
    
    // Generate JWT token (simplified version)
    $token = base64_encode(json_encode([
        'admin_id' => $admin['id'],
        'username' => $admin['username'],
        'role' => $admin['role'],
        'exp' => time() + (24 * 60 * 60) // 24 hours
    ]));
    
    // Update last login
    $stmt = $db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$admin['id']]);
    
    // Log the login
    error_log("Admin login: {$admin['username']} ({$admin['role']})");
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'full_name' => $admin['full_name'],
            'role' => $admin['role']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in admin-login.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error in admin-login.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
