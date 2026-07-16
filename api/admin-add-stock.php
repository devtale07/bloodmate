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
    
    // Validate required fields
    if (empty($_POST['stockBloodType']) || empty($_POST['stockUnits'])) {
        echo json_encode(['success' => false, 'message' => 'Blood type and units are required']);
        exit;
    }
    
    // Validate blood group
    $valid_blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    if (!in_array($_POST['stockBloodType'], $valid_blood_groups)) {
        echo json_encode(['success' => false, 'message' => 'Invalid blood group']);
        exit;
    }
    
    // Validate units
    $units = intval($_POST['stockUnits']);
    if ($units < 1 || $units > 100) {
        echo json_encode(['success' => false, 'message' => 'Units must be between 1 and 100']);
        exit;
    }
    
    // Update blood inventory
    $stmt = $db->prepare("
        UPDATE blood_inventory 
        SET units_available = units_available + ?, 
            last_updated = NOW() 
        WHERE blood_group = ?
    ");
    
    $result = $stmt->execute([$units, $_POST['stockBloodType']]);
    
    if ($result && $stmt->rowCount() > 0) {
        // Log the stock addition
        error_log("Blood stock added by admin {$admin['username']}: {$units} units of {$_POST['stockBloodType']}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Blood stock added successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add stock']);
    }
    
} catch (PDOException $e) {
    error_log("Database error in admin-add-stock.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error in admin-add-stock.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
