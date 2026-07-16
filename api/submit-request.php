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
    $required_fields = ['patient_name', 'blood_group_required', 'phone', 'hospital_location', 'urgency_level', 'request_date', 'units_needed'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            exit;
        }
    }
    
    // Validate blood group
    $valid_blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    if (!in_array($_POST['blood_group_required'], $valid_blood_groups)) {
        echo json_encode(['success' => false, 'message' => 'Invalid blood group']);
        exit;
    }
    
    // Validate urgency level
    $valid_urgency = ['Low', 'Medium', 'High', 'Critical'];
    if (!in_array($_POST['urgency_level'], $valid_urgency)) {
        echo json_encode(['success' => false, 'message' => 'Invalid urgency level']);
        exit;
    }
    
    // Validate units needed
    $units_needed = intval($_POST['units_needed']);
    if ($units_needed < 1 || $units_needed > 10) {
        echo json_encode(['success' => false, 'message' => 'Units needed must be between 1 and 10']);
        exit;
    }
    
    // Validate request date
    $request_date = $_POST['request_date'];
    $request_datetime = new DateTime($request_date);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    if ($request_datetime < $today) {
        echo json_encode(['success' => false, 'message' => 'Request date cannot be in the past']);
        exit;
    }
    
    // Insert blood request
    $stmt = $db->prepare("
        INSERT INTO recipient_requests 
        (name, blood_group_required, phone, email, hospital_location, urgency_level, 
         units_needed, request_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $_POST['patient_name'],
        $_POST['blood_group_required'],
        $_POST['phone'],
        $_POST['email'] ?? null,
        $_POST['hospital_location'],
        $_POST['urgency_level'],
        $units_needed,
        $request_date
    ]);
    
    if ($result) {
        $request_id = $db->lastInsertId();
        
        // Generate a user-friendly request ID
        $friendly_id = 'BR' . str_pad($request_id, 6, '0', STR_PAD_LEFT);
        
        // Log the request
        error_log("New blood request: ID $request_id, Patient: {$_POST['patient_name']}, Blood Group: {$_POST['blood_group_required']}, Urgency: {$_POST['urgency_level']}");
        
        // If it's a critical or high urgency request, you might want to send notifications here
        if (in_array($_POST['urgency_level'], ['Critical', 'High'])) {
            // TODO: Implement notification system for urgent requests
            error_log("URGENT BLOOD REQUEST: $friendly_id - {$_POST['blood_group_required']} needed urgently");
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Blood request submitted successfully',
            'request_id' => $friendly_id,
            'internal_id' => $request_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Request submission failed']);
    }
    
} catch (PDOException $e) {
    error_log("Database error in submit-request.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error in submit-request.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
