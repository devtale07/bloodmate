<?php
/**
 * BloodMate API Middleware
 * Include this file at the top of all API endpoints
 */

require_once '../config/Config.php';
require_once '../config/Database.php';
require_once '../config/Security.php';
require_once '../config/Auth.php';
require_once '../config/EmailService.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set security headers
Security::setSecurityHeaders();

// Set CORS headers
Security::setCorsHeaders();

// Enforce HTTPS in production
Security::enforceHttps();

// Global error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno]: $errstr in $errfile on line $errline");
    if (Config::isDebug()) {
        echo json_encode([
            'success' => false,
            'message' => "Error: $errstr",
            'file' => $errfile,
            'line' => $errline
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'An internal error occurred'
        ]);
    }
    exit;
});

// Global exception handler
set_exception_handler(function($exception) {
    error_log("Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    if (Config::isDebug()) {
        echo json_encode([
            'success' => false,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'An internal error occurred'
        ]);
    }
    exit;
});

/**
 * Send JSON response
 */
function sendJsonResponse($success, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Validate required fields
 */
function validateRequiredFields($data, $requiredFields) {
    $missing = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        sendJsonResponse(false, 'Missing required fields: ' . implode(', ', $missing), null, 400);
    }
}

/**
 * Sanitize all input data
 */
function sanitizeInputData($data) {
    if (is_array($data)) {
        return array_map('sanitizeInputData', $data);
    }
    return Security::sanitizeInput($data);
}
?>
