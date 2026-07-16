<?php
/**
 * BloodMate Email Service
 * Handles email sending using PHPMailer with SMTP
 */

require_once __DIR__ . '/Config.php';

class EmailService {
    private $mailer;
    private $config;

    public function __construct() {
        $this->config = Config::getSMTPConfig();
        $this->initializeMailer();
    }

    /**
     * Initialize PHPMailer
     */
    private function initializeMailer() {
        // Check if PHPMailer is available
        $phpmailerPath = __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
        
        if (file_exists($phpmailerPath)) {
            require_once $phpmailerPath;
            require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
            
            $this->mailer = new PHPMailer\PHPMailer\PHPMailer(true);
            
            try {
                // Server settings
                $this->mailer->isSMTP();
                $this->mailer->Host = $this->config['host'];
                $this->mailer->SMTPAuth = !empty($this->config['username']);
                $this->mailer->Username = $this->config['username'];
                $this->mailer->Password = $this->config['password'];
                $this->mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $this->mailer->Port = intval($this->config['port']);
                
                // Recipients
                $this->mailer->setFrom($this->config['from'], $this->config['from_name']);
                
                // Content
                $this->mailer->isHTML(true);
                $this->mailer->CharSet = 'UTF-8';
            } catch (Exception $e) {
                error_log("PHPMailer initialization failed: " . $e->getMessage());
                $this->mailer = null;
            }
        } else {
            // Fallback to mail() function if PHPMailer not available
            $this->mailer = null;
        }
    }

    /**
     * Send email
     */
    public function sendEmail($to, $subject, $body, $altBody = null) {
        try {
            if ($this->mailer) {
                // Use PHPMailer
                $this->mailer->clearAddresses();
                $this->mailer->addAddress($to);
                $this->mailer->Subject = $subject;
                $this->mailer->Body = $body;
                $this->mailer->AltBody = $altBody ?: strip_tags($body);
                
                return $this->mailer->send();
            } else {
                // Fallback to mail() function
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                $headers .= "From: {$this->config['from_name']} <{$this->config['from']}>\r\n";
                $headers .= "Reply-To: {$this->config['from']}\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();
                
                return mail($to, $subject, $body, $headers);
            }
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send donor registration email with credentials
     */
    public function sendDonorCredentials($email, $name, $username, $password) {
        $appUrl = Config::get('APP_URL');
        
        $body = "
        <html>
        <head>
            <title>BloodMate Donor Account Credentials</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #e53935 0%, #d32f2f 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #ddd; }
                .credentials { background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #e53935; margin: 20px 0; }
                .credentials code { background: #f0f0f0; padding: 5px 10px; border-radius: 4px; }
                .button { display: inline-block; padding: 12px 24px; background: #e53935; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { font-size: 12px; color: #999; text-align: center; margin-top: 30px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin: 0; font-size: 24px;'>🩸 BloodMate</h1>
                </div>
                <div class='content'>
                    <h2 style='color: #e53935; margin-top: 0;'>Welcome to BloodMate!</h2>
                    <p>Dear <strong>$name</strong>,</p>
                    <p>Thank you for registering as a blood donor. Your donor account has been successfully created. Here are your login credentials:</p>
                    
                    <div class='credentials'>
                        <p style='margin: 10px 0;'><strong>Username:</strong> <code>$username</code></p>
                        <p style='margin: 10px 0;'><strong>Password:</strong> <code>$password</code></p>
                    </div>
                    
                    <p style='color: #666; font-size: 14px;'><strong>⚠️ Important:</strong> Please save these credentials securely. You will need them to login to your donor account.</p>
                    
                    <p style='margin-top: 30px;'>To login, visit our authentication page:</p>
                    <p><a href='$appUrl/auth.html' class='button'>Click here to login</a></p>
                    
                    <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
                    
                    <div class='footer'>
                        This is an automated email. Please do not reply to this message.<br>
                        If you did not create this account, please contact us immediately.
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $subject = 'Your BloodMate Donor Account Credentials';
        $altBody = "Welcome to BloodMate! Your username: $username, Password: $password. Login at: $appUrl/auth.html";
        
        return $this->sendEmail($email, $subject, $body, $altBody);
    }

    /**
     * Send blood request confirmation email
     */
    public function sendBloodRequestConfirmation($email, $name, $bloodGroup, $requestId) {
        $appUrl = Config::get('APP_URL');
        
        $body = "
        <html>
        <head>
            <title>Blood Request Confirmation</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #e53935 0%, #d32f2f 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #ddd; }
                .info-box { background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #e53935; margin: 20px 0; }
                .footer { font-size: 12px; color: #999; text-align: center; margin-top: 30px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin: 0; font-size: 24px;'>🩸 BloodMate</h1>
                </div>
                <div class='content'>
                    <h2 style='color: #e53935; margin-top: 0;'>Blood Request Received</h2>
                    <p>Dear <strong>$name</strong>,</p>
                    <p>We have received your blood request. Our team is working to find matching donors for you.</p>
                    
                    <div class='info-box'>
                        <p><strong>Request ID:</strong> #$requestId</p>
                        <p><strong>Blood Group Required:</strong> $bloodGroup</p>
                        <p><strong>Status:</strong> Pending</p>
                    </div>
                    
                    <p>We will contact you as soon as we find a matching donor. For urgent requests, please call our emergency helpline.</p>
                    
                    <p>Track your request status at: <a href='$appUrl'>BloodMate Portal</a></p>
                    
                    <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
                    
                    <div class='footer'>
                        This is an automated email. Please do not reply to this message.
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $subject = "Blood Request #$requestId Received";
        $altBody = "Your blood request #$requestId for $bloodGroup has been received. We will contact you soon.";
        
        return $this->sendEmail($email, $subject, $body, $altBody);
    }

    /**
     * Send contact form confirmation
     */
    public function sendContactConfirmation($email, $name) {
        $appUrl = Config::get('APP_URL');
        
        $body = "
        <html>
        <head>
            <title>Contact Message Received</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #e53935 0%, #d32f2f 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #ddd; }
                .footer { font-size: 12px; color: #999; text-align: center; margin-top: 30px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin: 0; font-size: 24px;'>🩸 BloodMate</h1>
                </div>
                <div class='content'>
                    <h2 style='color: #e53935; margin-top: 0;'>Message Received</h2>
                    <p>Dear <strong>$name</strong>,</p>
                    <p>Thank you for contacting BloodMate. We have received your message and will get back to you within 24-48 hours.</p>
                    
                    <p>If your inquiry is urgent, please call our helpline directly.</p>
                    
                    <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
                    
                    <div class='footer'>
                        This is an automated email. Please do not reply to this message.
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $subject = 'Thank you for contacting BloodMate';
        $altBody = "Thank you for contacting BloodMate. We have received your message and will respond soon.";
        
        return $this->sendEmail($email, $subject, $body, $altBody);
    }

    /**
     * Send emergency alert to admins
     */
    public function sendEmergencyAlert($requestDetails) {
        $appUrl = Config::get('APP_URL');
        $adminEmail = Config::get('ADMIN_EMAIL');
        
        $body = "
        <html>
        <head>
            <title>🚨 Emergency Blood Request</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #d32f2f; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #ddd; }
                .emergency-box { background: #ffebee; padding: 20px; border-radius: 8px; border-left: 4px solid #d32f2f; margin: 20px 0; }
                .button { display: inline-block; padding: 12px 24px; background: #d32f2f; color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin: 0; font-size: 24px;'>🚨 EMERGENCY ALERT</h1>
                </div>
                <div class='content'>
                    <h2 style='color: #d32f2f; margin-top: 0;'>Critical Blood Request</h2>
                    
                    <div class='emergency-box'>
                        <p><strong>Patient Name:</strong> {$requestDetails['name']}</p>
                        <p><strong>Blood Group:</strong> {$requestDetails['blood_group']}</p>
                        <p><strong>Hospital:</strong> {$requestDetails['hospital']}</p>
                        <p><strong>Urgency:</strong> {$requestDetails['urgency']}</p>
                        <p><strong>Phone:</strong> {$requestDetails['phone']}</p>
                    </div>
                    
                    <p><strong>Action Required:</strong> Please review and process this emergency request immediately.</p>
                    
                    <p><a href='$appUrl/admin/dashboard.html' class='button'>Access Admin Dashboard</a></p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $subject = "🚨 EMERGENCY: Critical Blood Request";
        $altBody = "EMERGENCY: Critical blood request received. Please check admin dashboard immediately.";
        
        return $this->sendEmail($adminEmail, $subject, $body, $altBody);
    }
}
?>
