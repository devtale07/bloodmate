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
    
    if (empty($_POST['fullname']) || empty($_POST['email']) || empty($_POST['phone'])) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$_POST['email']]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit;
    }
    
    $email_parts = explode('@', $_POST['email']);
    $base_username = preg_replace('/[^a-zA-Z0-9]/', '', $email_parts[0]);
    $username = $base_username;
    $counter = 1;
    
    while (true) {
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if (!$stmt->fetch()) break;
        $username = $base_username . $counter;
        $counter++;
    }
    
    $password = generateRandomPassword(12);
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("
        INSERT INTO users (username, password_hash, email, full_name, phone, is_verified)
        VALUES (?, ?, ?, ?, ?, TRUE)
    ");
    
    if ($stmt->execute([$username, $password_hash, $_POST['email'], $_POST['fullname'], $_POST['phone']])) {
        $email_sent = sendCredentialsEmail($_POST['email'], $_POST['fullname'], $username, $password);
        
        if ($email_sent) {
            echo json_encode([
                'success' => true,
                'message' => 'Account created successfully. Please check your email for login credentials.',
                'username' => $username
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Account created but email could not be sent. Username: ' . $username . ' | Password: ' . $password,
                'username' => $username,
                'password' => $password
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create account']);
    }
    
} catch (PDOException $e) {
    error_log("Database error in register-user.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Error in register-user.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}


function generateRandomPassword($length = 12) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}

function sendCredentialsEmail($email, $name, $username, $password) {
    require_once '../libs/PHPMailer/src/Exception.php';
    require_once '../libs/PHPMailer/src/PHPMailer.php';
    require_once '../libs/PHPMailer/src/SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->SMTPDebug = 2; // ← Add this line temporarily
    // ... rest of the code
    // function sendCredentialsEmail($email, $name, $username, $password) {
    // ─── Load PHPMailer ───────────────────────────────────────────
    require_once '../libs/PHPMailer/src/Exception.php';
    require_once '../libs/PHPMailer/src/PHPMailer.php';
    require_once '../libs/PHPMailer/src/SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // ─── SMTP Settings ────────────────────────────────────────
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'bloodmateco.com@gmail.com';  // ← YOUR Gmail address
        $mail->Password   = 'nmlf vgiv atvd rqfa ';   // ← Gmail App Password (16 chars)
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // ─── Sender & Recipient ───────────────────────────────────
        $mail->setFrom('bloodmateco.com@gmail.com', 'BloodMate');
        $mail->addAddress($email, $name);

        // ─── Email Content ────────────────────────────────────────
        $mail->isHTML(true);
        $mail->Subject = 'Your BloodMate Account Credentials';
        $mail->Body    = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: linear-gradient(135deg, #e53935 0%, #d32f2f 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                <h1 style='margin: 0; font-size: 24px;'>🩸 BloodMate</h1>
            </div>
            <div style='background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #ddd;'>
                <h2 style='color: #e53935; margin-top: 0;'>Welcome to BloodMate!</h2>
                <p>Dear <strong>$name</strong>,</p>
                <p>Your account has been successfully created. Here are your login credentials:</p>
                <div style='background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #e53935; margin: 20px 0;'>
                    <p style='margin: 10px 0;'><strong>Username:</strong> <code style='background: #f0f0f0; padding: 5px 10px; border-radius: 4px;'>$username</code></p>
                    <p style='margin: 10px 0;'><strong>Password:</strong> <code style='background: #f0f0f0; padding: 5px 10px; border-radius: 4px;'>$password</code></p>
                </div>
                <p style='color: #666; font-size: 14px;'><strong>⚠️ Important:</strong> Please save these credentials securely.</p>
                <p>To login, visit: <a href='http://localhost/BloodMate/auth.html' style='color: #e53935;'>Click here to login</a></p>
                <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
                <p style='font-size: 12px; color: #999; text-align: center;'>
                    This is an automated email. Please do not reply.<br>
                    If you did not create this account, contact us immediately.
                </p>
            </div>
        </div>
        </body>
        </html>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("PHPMailer error: " . $mail->ErrorInfo);
        return false;
    }
}
?>