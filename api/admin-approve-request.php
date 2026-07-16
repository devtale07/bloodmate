<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';

// Simple token validation
function validateToken($token) {
    if (!$token) return false;
    
    $token = str_replace('Bearer ', '', $token);
    $decoded = json_decode(base64_decode($token), true);
    
    if (!$decoded || !isset($decoded['exp']) || $decoded['exp'] < time()) {
        return false;
    }
    
    return $decoded;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$admin = validateToken($token);

if (!$admin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['request_id'])) {
        echo json_encode(['success' => false, 'message' => 'Request ID is required']);
        exit;
    }
    
    $request_id = intval($input['request_id']);
    
    // Update request status to approved
    $stmt = $db->prepare("
        UPDATE recipient_requests 
        SET status = 'Approved', 
            admin_notes = CONCAT(COALESCE(admin_notes, ''), 'Approved by admin: {$admin['username']} on ', NOW())
        WHERE id = ? AND status = 'Pending'
    ");
    
    $result = $stmt->execute([$request_id]);
    
    if ($result && $stmt->rowCount() > 0) {
        // Log the approval
        error_log("Blood request approved by admin {$admin['username']}: Request ID $request_id");
        
        echo json_encode([
            'success' => true,
            'message' => 'Request approved successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
    }
    
} catch (PDOException $e) {
    error_log("Database error in admin-approve-request.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error in admin-approve-request.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
