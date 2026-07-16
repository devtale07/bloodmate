<?php
/**
 * BloodMate AI Chatbot API
 * Integrates with OpenAI API for intelligent responses
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/Config.php';
require_once '../config/Security.php';

// Set security headers
Security::setSecurityHeaders();
Security::setCorsHeaders();

// Apply rate limiting
Security::rateLimit();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = Security::getJsonInput();
    
    // Validate input
    if (empty($input['message'])) {
        echo json_encode(['success' => false, 'message' => 'Message is required']);
        exit;
    }
    
    $userMessage = Security::sanitizeInput($input['message']);
    $conversationHistory = $input['history'] ?? [];
    
    // Get OpenAI API key
    $apiKey = Config::get('OPENAI_API_KEY');
    
    if (empty($apiKey) || $apiKey === 'your-openai-api-key-here') {
        // Fallback to rule-based responses if no API key configured
        $response = generateRuleBasedResponse($userMessage);
        echo json_encode([
            'success' => true,
            'response' => $response,
            'source' => 'rule-based'
        ]);
        exit;
    }
    
    // Build conversation context
    $systemPrompt = "You are BloodMate Assistant, a helpful AI assistant for a blood donation management system called BloodMate. 
    Your role is to help users with:
    - Blood donation eligibility questions
    - Finding donation centers
    - Blood type compatibility information
    - Emergency blood request procedures
    - Donor registration process
    - Blood request process
    - General information about BloodMate
    
    Be friendly, professional, and concise. If you don't know something specific about BloodMate's operations, provide general helpful information and suggest they contact support.
    Always prioritize safety and medical accuracy. For medical emergencies, always recommend calling emergency services.";
    
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt]
    ];
    
    // Add conversation history
    foreach ($conversationHistory as $msg) {
        $role = $msg['role'] === 'user' ? 'user' : 'assistant';
        $messages[] = ['role' => $role, 'content' => $msg['content']];
    }
    
    // Add current message
    $messages[] = ['role' => 'user', 'content' => $userMessage];
    
    // Call OpenAI API
    $response = callOpenAI($messages, $apiKey);
    
    if ($response['success']) {
        echo json_encode([
            'success' => true,
            'response' => $response['message'],
            'source' => 'openai'
        ]);
    } else {
        // Fallback to rule-based on API failure
        $fallbackResponse = generateRuleBasedResponse($userMessage);
        echo json_encode([
            'success' => true,
            'response' => $fallbackResponse,
            'source' => 'fallback'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Chatbot error: " . $e->getMessage());
    
    // Fallback to rule-based response on error
    $fallbackResponse = generateRuleBasedResponse($userMessage ?? '');
    echo json_encode([
        'success' => true,
        'response' => $fallbackResponse,
        'source' => 'error-fallback'
    ]);
}

/**
 * Call OpenAI API
 */
function callOpenAI($messages, $apiKey) {
    $url = 'https://api.openai.com/v1/chat/completions';
    $model = Config::get('OPENAI_MODEL', 'gpt-3.5-turbo');
    
    $data = [
        'model' => $model,
        'messages' => $messages,
        'max_tokens' => 500,
        'temperature' => 0.7
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("OpenAI API curl error: " . $error);
        return ['success' => false, 'message' => 'API request failed'];
    }
    
    if ($httpCode !== 200) {
        error_log("OpenAI API returned HTTP $httpCode: $response");
        return ['success' => false, 'message' => 'API error'];
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['choices'][0]['message']['content'])) {
        return ['success' => true, 'message' => $result['choices'][0]['message']['content']];
    }
    
    return ['success' => false, 'message' => 'Invalid response format'];
}

/**
 * Generate rule-based responses (fallback)
 */
function generateRuleBasedResponse($message) {
    $lowerMessage = strtolower($message);
    $appUrl = Config::get('APP_URL', 'http://localhost');
    
    // Blood donation eligibility
    if (strpos($lowerMessage, 'eligible') !== false || strpos($lowerMessage, 'eligibility') !== false) {
        return "To be eligible to donate blood, you must:\n\n" .
               "• Be between 18-65 years old\n" .
               "• Weigh at least 50 kg (110 lbs)\n" .
               "• Be in good general health\n" .
               "• Not have donated blood in the last 3 months\n\n" .
               "For a detailed eligibility check, try our <a href=\"$appUrl/ai-eligibility.html\" style=\"color:#e53935\">AI Eligibility Test</a>.";
    }
    
    // Where to donate
    if (strpos($lowerMessage, 'donate') !== false && (strpos($lowerMessage, 'where') !== false || strpos($lowerMessage, 'center') !== false)) {
        return "You can donate blood at:\n\n" .
               "• Registered blood banks in your area\n" .
               "• Partner hospitals\n" .
               "• Mobile blood donation camps\n\n" .
               "Check our <a href=\"$appUrl/blood-inventory.html\" style=\"color:#e53935\">Blood Inventory</a> page to find available centers near you.";
    }
    
    // Blood type compatibility
    if (strpos($lowerMessage, 'blood type') !== false || strpos($lowerMessage, 'compatibility') !== false) {
        return "Blood type compatibility:\n\n" .
               "• <strong>O-</strong> can donate to everyone (universal donor)\n" .
               "• <strong>AB+</strong> can receive from everyone (universal recipient)\n" .
               "• <strong>A+</strong> can donate to A+ and AB+\n" .
               "• <strong>B+</strong> can donate to B+ and AB+\n\n" .
               "View our <a href=\"$appUrl/blood-inventory.html\" style=\"color:#e53935\">Blood Types</a> section for more details.";
    }
    
    // Emergency
    if (strpos($lowerMessage, 'emergency') !== false) {
        return "For emergency blood requests:\n\n" .
               "🚨 Call our 24/7 helpline: +91 123 456 7890\n" .
               "🚨 Visit our <a href=\"$appUrl/database/emergency.html\" style=\"color:#e53935\">Emergency Portal</a>\n" .
               "🚨 Contact nearest hospital directly\n\n" .
               "Every second counts in emergencies!";
    }
    
    // Registration
    if (strpos($lowerMessage, 'register') !== false || strpos($lowerMessage, 'sign up') !== false) {
        return "To register as a donor:\n\n" .
               "1. Visit our <a href=\"$appUrl/donor-registration.html\" style=\"color:#e53935\">Donor Registration</a> page\n" .
               "2. Fill in your details\n" .
               "3. Complete health questionnaire\n" .
               "4. Get verified and start saving lives!\n\n" .
               "Registration takes less than 3 minutes.";
    }
    
    // Request blood
    if (strpos($lowerMessage, 'request') !== false || strpos($lowerMessage, 'need blood') !== false) {
        return "To request blood:\n\n" .
               "• Use our <a href=\"$appUrl/recipient-request.html\" style=\"color:#e53935\">Blood Request</a> form\n" .
               "• Provide patient details and blood type\n" .
               "• Our system will match you with donors\n" .
               "• For urgent needs, use emergency services\n\n" .
               "We prioritize critical cases.";
    }
    
    // Contact
    if (strpos($lowerMessage, 'contact') !== false || strpos($lowerMessage, 'help') !== false || strpos($lowerMessage, 'support') !== false) {
        return "Contact BloodMate:\n\n" .
               "📞 Phone: +91 123 456 7890\n" .
               "📧 Email: info@bloodmate.com\n" .
               "📍 Address: 123 Healthcare Street, Medical City\n\n" .
               "Or visit our <a href=\"$appUrl/contact.html\" style=\"color:#e53935\">Contact Page</a> for more options.";
    }
    
    // Default response
    return "I'm here to help! You can ask me about:\n\n" .
           "• Blood donation eligibility\n" .
           "• Where to donate blood\n" .
           "• Blood type compatibility\n" .
           "• Emergency blood requests\n" .
           "• How to register as a donor\n" .
           "• How to request blood\n\n" .
           "What would you like to know?";
}
?>
