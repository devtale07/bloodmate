<?php
require_once 'middleware.php';

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
    validateRequiredFields($input, ['patient_name', 'blood_group_required', 'phone', 'hospital_location', 'urgency_level', 'request_date', 'units_needed']);
    
    // Validate blood group
    $valid_blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    if (!in_array($input['blood_group_required'], $valid_blood_groups)) {
        sendJsonResponse(false, 'Invalid blood group', null, 400);
    }
    
    // Validate urgency level
    $valid_urgency = ['Low', 'Medium', 'High', 'Critical'];
    if (!in_array($input['urgency_level'], $valid_urgency)) {
        sendJsonResponse(false, 'Invalid urgency level', null, 400);
    }
    
    // Validate units needed
    $units_needed = intval($input['units_needed']);
    if ($units_needed < 1 || $units_needed > 10) {
        sendJsonResponse(false, 'Units needed must be between 1 and 10', null, 400);
    }
    
    // Validate request date
    $request_date = $input['request_date'];
    $request_datetime = new DateTime($request_date);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    if ($request_datetime < $today) {
        sendJsonResponse(false, 'Request date cannot be in the past', null, 400);
    }
    
    // Insert blood request
    $stmt = $db->prepare("
        INSERT INTO recipient_requests 
        (name, blood_group_required, phone, email, hospital_location, urgency_level, 
         units_needed, request_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $input['patient_name'],
        $input['blood_group_required'],
        $input['phone'],
        $input['email'] ?? null,
        $input['hospital_location'],
        $input['urgency_level'],
        $units_needed,
        $request_date
    ]);
    
    if ($result) {
        $request_id = $db->lastInsertId();
        
        // Generate a user-friendly request ID
        $friendly_id = 'BR' . str_pad($request_id, 6, '0', STR_PAD_LEFT);
        
        // Log the request
        Logger::info('New blood request', [
            'request_id' => $request_id,
            'patient_name' => $input['patient_name'],
            'blood_group' => $input['blood_group_required'],
            'urgency' => $input['urgency_level']
        ]);
        
        // If it's a critical or high urgency request, send notifications
        if (in_array($input['urgency_level'], ['Critical', 'High'])) {
            Logger::warning('URGENT blood request', [
                'request_id' => $friendly_id,
                'blood_group' => $input['blood_group_required'],
                'urgency' => $input['urgency_level']
            ]);
        }
        
        sendJsonResponse(true, 'Blood request submitted successfully', [
            'request_id' => $friendly_id,
            'internal_id' => $request_id
        ]);
    } else {
        sendJsonResponse(false, 'Request submission failed', null, 500);
    }
    
} catch (PDOException $e) {
    Logger::error("Database error in submit-request.php: " . $e->getMessage());
    sendJsonResponse(false, 'Database error occurred', null, 500);
} catch (Exception $e) {
    Logger::error("Error in submit-request.php: " . $e->getMessage());
    sendJsonResponse(false, 'An error occurred', null, 500);
}
?>
