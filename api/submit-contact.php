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
    $required_fields = ['name', 'email', 'subject', 'message'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            exit;
        }
    }
    
    // Validate email format
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    // Validate message length
    if (strlen($_POST['message']) < 10) {
        echo json_encode(['success' => false, 'message' => 'Message must be at least 10 characters long']);
        exit;
    }
    
    if (strlen($_POST['message']) > 1000) {
        echo json_encode(['success' => false, 'message' => 'Message must be less than 1000 characters']);
        exit;
    }
    
    // Insert contact message
    $stmt = $db->prepare("
        INSERT INTO contact_messages (name, email, phone, subject, message) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $_POST['name'],
        $_POST['email'],
        $_POST['phone'] ?? null,
        $_POST['subject'],
        $_POST['message']
    ]);
    
    if ($result) {
        $message_id = $db->lastInsertId();
        
        // Log the contact message
        error_log("New contact message: ID $message_id, From: {$_POST['name']} ({$_POST['email']}), Subject: {$_POST['subject']}");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Message sent successfully',
            'message_id' => $message_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send message']);
    }
    
} catch (PDOException $e) {
    error_log("Database error in submit-contact.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error in submit-contact.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
