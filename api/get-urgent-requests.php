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
    
    // Get urgent blood requests (High and Critical priority, pending status)
    $stmt = $db->prepare("
        SELECT * FROM recipient_requests 
        WHERE urgency_level IN ('High', 'Critical') 
        AND status = 'Pending'
        AND request_date >= CURDATE()
        ORDER BY 
            CASE urgency_level 
                WHEN 'Critical' THEN 1 
                WHEN 'High' THEN 2 
                ELSE 3 
            END,
            request_date ASC
        LIMIT 10
    ");
    $stmt->execute();
    $requests = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'requests' => $requests
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get-urgent-requests.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error in get-urgent-requests.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
