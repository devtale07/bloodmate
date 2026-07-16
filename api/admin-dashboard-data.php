<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
    
    // Get dashboard statistics
    $stats = [];
    
    // Total donors
    $stmt = $db->query("SELECT COUNT(*) as count FROM donors");
    $stats['totalDonors'] = $stmt->fetch()['count'];
    
    // Pending requests
    $stmt = $db->query("SELECT COUNT(*) as count FROM recipient_requests WHERE status = 'Pending'");
    $stats['pendingRequests'] = $stmt->fetch()['count'];
    
    // Total blood units
    $stmt = $db->query("SELECT SUM(units_available) as total FROM blood_inventory");
    $stats['totalUnits'] = $stmt->fetch()['total'] ?? 0;
    
    // Urgent requests
    $stmt = $db->query("SELECT COUNT(*) as count FROM recipient_requests WHERE urgency_level IN ('High', 'Critical') AND status = 'Pending'");
    $stats['urgentRequests'] = $stmt->fetch()['count'];
    
    // Recent blood requests
    $stmt = $db->prepare("
        SELECT * FROM recipient_requests 
        WHERE status = 'Pending'
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_requests = $stmt->fetchAll();
    
    // Blood inventory
    $stmt = $db->query("SELECT * FROM blood_inventory ORDER BY blood_group");
    $inventory = $stmt->fetchAll();
    
    // Recent donors
    $stmt = $db->prepare("
        SELECT * FROM donors 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_donors = $stmt->fetchAll();
    
    // Recent messages
    $stmt = $db->prepare("
        SELECT * FROM contact_messages 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_messages = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'recent_requests' => $recent_requests,
        'inventory' => $inventory,
        'recent_donors' => $recent_donors,
        'recent_messages' => $recent_messages
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in admin-dashboard-data.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error in admin-dashboard-data.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
