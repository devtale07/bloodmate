<?php
require_once 'middleware.php';

// Apply rate limiting (stricter for registration)
Security::rateLimit(null, 5, 300); // 5 requests per 5 minutes

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Method not allowed', null, 405);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get and sanitize input
    $input = Security::getJsonInput();
    $input = sanitizeInputData($input);
    
    // Validate required fields
    validateRequiredFields($input, ['name', 'age', 'blood_group', 'phone', 'city']);
    
    // Validate age
    $age = intval($input['age']);
    if ($age < 18 || $age > 65) {
        sendJsonResponse(false, 'Age must be between 18 and 65 years', null, 400);
    }
    
    // Validate blood group
    $valid_blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    if (!in_array($input['blood_group'], $valid_blood_groups)) {
        sendJsonResponse(false, 'Invalid blood group', null, 400);
    }
    
    // Validate email if provided
    if (!empty($input['email'])) {
        if (!Security::validateEmail($input['email'])) {
            sendJsonResponse(false, 'Invalid email format', null, 400);
        }
        
        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM donors WHERE email = ?");
        $stmt->execute([$input['email']]);
        if ($stmt->fetch()) {
            sendJsonResponse(false, 'Email already registered', null, 409);
        }
    }
    
    // Validate phone
    if (!Security::validatePhone($input['phone'])) {
        sendJsonResponse(false, 'Invalid phone number', null, 400);
    }
    
    // Validate last donation date if provided
    $last_donation_date = null;
    if (!empty($input['last_donation'])) {
        $last_donation_date = $input['last_donation'];
        $donation_date = new DateTime($last_donation_date);
        $today = new DateTime();
        $interval = $today->diff($donation_date);
        
        if ($interval->days < 56) {
            sendJsonResponse(false, 'You must wait at least 56 days between donations', null, 400);
        }
    }
    
    // Generate username from email or phone
    if (!empty($input['email'])) {
        $email_parts = explode('@', $input['email']);
        $base_username = preg_replace('/[^a-zA-Z0-9]/', '', $email_parts[0]);
    } else {
        $base_username = preg_replace('/[^0-9]/', '', $input['phone']);
    }
    $username = $base_username;
    $counter = 1;
    
    // Ensure username is unique
    while (true) {
        $stmt = $db->prepare("SELECT id FROM donors WHERE username = ?");
        $stmt->execute([$username]);
        if (!$stmt->fetch()) {
            break;
        }
        $username = $base_username . $counter;
        $counter++;
    }
    
    // Generate random password
    $password = Auth::generateSecureToken(12);
    $password_hash = Auth::hashPassword($password);
    
    // Insert donor with credentials
    $stmt = $db->prepare("
        INSERT INTO donors (name, age, blood_group, phone, email, city, address, last_donation_date, username, password_hash) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $input['name'],
        $age,
        $input['blood_group'],
        $input['phone'],
        $input['email'] ?? null,
        $input['city'],
        $input['address'] ?? null,
        $last_donation_date,
        $username,
        $password_hash
    ]);
    
    if ($result) {
        $donor_id = $db->lastInsertId();
        
        // Log the registration
        error_log("New donor registered: ID $donor_id, Name: {$input['name']}, Blood Group: {$input['blood_group']}, Username: $username");
        Security::logSecurityEvent('donor_registration', ['donor_id' => $donor_id, 'username' => $username]);
        
        // Send email with credentials if email provided
        $email_sent = false;
        if (!empty($input['email'])) {
            $emailService = new EmailService();
            $email_sent = $emailService->sendDonorCredentials($input['email'], $input['name'], $username, $password);
        }
        
        $responseData = [
            'donor_id' => $donor_id,
            'username' => $username
        ];
        
        if ($email_sent) {
            sendJsonResponse(true, 'Registration successful. Please check your email for login credentials.', $responseData);
        } else {
            // Even if email fails, account is created
            if (!empty($input['email'])) {
                $responseData['password'] = $password;
                sendJsonResponse(true, 'Registration successful. However, there was an issue sending the email. Your credentials are shown below.', $responseData);
            } else {
                $responseData['password'] = $password;
                sendJsonResponse(true, 'Registration successful. Your credentials are shown below.', $responseData);
            }
        }
    } else {
        sendJsonResponse(false, 'Registration failed', null, 500);
    }
    
} catch (PDOException $e) {
    error_log("Database error in register-donor.php: " . $e->getMessage());
    sendJsonResponse(false, 'Database error occurred', null, 500);
} catch (Exception $e) {
    error_log("Error in register-donor.php: " . $e->getMessage());
    sendJsonResponse(false, 'An error occurred', null, 500);
}
?>
