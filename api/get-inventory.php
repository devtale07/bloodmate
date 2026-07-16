<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get blood inventory
    $stmt = $db->prepare("SELECT * FROM blood_inventory ORDER BY blood_group");
    $stmt->execute();
    $inventory = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'inventory' => $inventory,
        'last_updated' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get-inventory.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error in get-inventory.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
