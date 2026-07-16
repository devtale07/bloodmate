<?php
/**
 * BloodMate Logging System
 * Comprehensive logging for errors, security events, and application monitoring
 */

require_once __DIR__ . '/Config.php';

class Logger {
    private static $logDir;
    private static $logLevels = ['DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL'];
    
    /**
     * Initialize logger
     */
    private static function init() {
        self::$logDir = __DIR__ . '/../logs';
        
        // Create logs directory if it doesn't exist
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
    }

    /**
     * Log message with level
     */
    public static function log($level, $message, $context = []) {
        self::init();
        
        if (!in_array($level, self::$logLevels)) {
            $level = 'INFO';
        }
        
        // Only log DEBUG messages in development mode
        if ($level === 'DEBUG' && !Config::isDebug()) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logFile = self::$logDir . '/' . strtolower($level) . '.log';
        
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logEntry = "[$timestamp] [$level] $message$contextStr\n";
        
        // Write to log file
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        
        // Also write to combined log
        $combinedFile = self::$logDir . '/application.log';
        file_put_contents($combinedFile, $logEntry, FILE_APPEND);
        
        // For critical errors, also send email alert
        if ($level === 'CRITICAL') {
            self::sendCriticalAlert($message, $context);
        }
    }

    /**
     * Debug level log
     */
    public static function debug($message, $context = []) {
        self::log('DEBUG', $message, $context);
    }

    /**
     * Info level log
     */
    public static function info($message, $context = []) {
        self::log('INFO', $message, $context);
    }

    /**
     * Warning level log
     */
    public static function warning($message, $context = []) {
        self::log('WARNING', $message, $context);
    }

    /**
     * Error level log
     */
    public static function error($message, $context = []) {
        self::log('ERROR', $message, $context);
    }

    /**
     * Critical level log
     */
    public static function critical($message, $context = []) {
        self::log('CRITICAL', $message, $context);
    }

    /**
     * Log database query
     */
    public static function logQuery($query, $params = [], $executionTime = null) {
        if (!Config::isDebug()) {
            return;
        }
        
        $context = [
            'query' => $query,
            'params' => $params,
            'execution_time' => $executionTime
        ];
        
        self::debug('Database query executed', $context);
    }

    /**
     * Log API request
     */
    public static function logApiRequest($endpoint, $method, $params = [], $response = null, $executionTime = null) {
        $context = [
            'endpoint' => $endpoint,
            'method' => $method,
            'params' => $params,
            'response_status' => $response['success'] ?? null,
            'execution_time' => $executionTime
        ];
        
        self::info('API request: ' . $method . ' ' . $endpoint, $context);
    }

    /**
     * Log user activity
     */
    public static function logUserActivity($userId, $action, $details = []) {
        $context = array_merge([
            'user_id' => $userId,
            'action' => $action,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ], $details);
        
        self::info('User activity: ' . $action, $context);
    }

    /**
     * Log security event
     */
    public static function logSecurityEvent($event, $details = []) {
        $context = array_merge([
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => time()
        ], $details);
        
        self::warning('Security event: ' . $event, $context);
        
        // Also write to security log
        $securityFile = self::$logDir . '/security.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] IP: {$context['ip']} | Event: $event | User-Agent: {$context['user_agent']} | Details: " . json_encode($details) . "\n";
        file_put_contents($securityFile, $logEntry, FILE_APPEND);
    }

    /**
     * Send critical alert email
     */
    private static function sendCriticalAlert($message, $context) {
        try {
            $adminEmail = Config::get('ADMIN_EMAIL');
            if (empty($adminEmail)) {
                return;
            }
            
            $appName = Config::get('APP_NAME', 'BloodMate');
            $timestamp = date('Y-m-d H:i:s');
            
            $body = "
            <html>
            <head>
                <title>Critical Error Alert - $appName</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #d32f2f; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #ddd; }
                    .error-box { background: #ffebee; padding: 20px; border-radius: 8px; border-left: 4px solid #d32f2f; margin: 20px 0; }
                    .context { background: white; padding: 15px; border-radius: 8px; margin: 20px 0; font-family: monospace; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1 style='margin: 0; font-size: 24px;'>🚨 CRITICAL ERROR ALERT</h1>
                    </div>
                    <div class='content'>
                        <h2 style='color: #d32f2f; margin-top: 0;'>Critical Error Detected</h2>
                        
                        <div class='error-box'>
                            <p><strong>Application:</strong> $appName</p>
                            <p><strong>Time:</strong> $timestamp</p>
                            <p><strong>Message:</strong> $message</p>
                        </div>
                        
                        <h3>Context:</h3>
                        <div class='context'>" . htmlspecialchars(json_encode($context, JSON_PRETTY_PRINT)) . "</div>
                        
                        <p><strong>Action Required:</strong> Please investigate this critical error immediately.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $subject = "🚨 CRITICAL: $appName Error Alert";
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: $appName <noreply@bloodmate.com>\r\n";
            
            mail($adminEmail, $subject, $body, $headers);
        } catch (Exception $e) {
            // Don't throw errors in error handling
            error_log("Failed to send critical alert email: " . $e->getMessage());
        }
    }

    /**
     * Get log statistics
     */
    public static function getLogStats() {
        self::init();
        
        $stats = [];
        
        foreach (self::$logLevels as $level) {
            $logFile = self::$logDir . '/' . strtolower($level) . '.log';
            if (file_exists($logFile)) {
                $lines = file($logFile);
                $stats[strtolower($level)] = count($lines);
            } else {
                $stats[strtolower($level)] = 0;
            }
        }
        
        return $stats;
    }

    /**
     * Clean old log files
     */
    public static function cleanOldLogs($daysToKeep = 30) {
        self::init();
        
        $cutoffTime = time() - ($daysToKeep * 86400);
        
        foreach (glob(self::$logDir . '/*.log') as $file) {
            if (filemtime($file) < $cutoffTime) {
                // Archive instead of delete
                $archiveFile = str_replace('.log', '.' . date('Y-m-d') . '.log', $file);
                rename($file, $archiveFile);
            }
        }
        
        // Delete archives older than 90 days
        $archiveCutoff = time() - (90 * 86400);
        foreach (glob(self::$logDir . '/*.log.*') as $file) {
            if (filemtime($file) < $archiveCutoff) {
                unlink($file);
            }
        }
    }

    /**
     * Get recent log entries
     */
    public static function getRecentLogs($level = 'error', $lines = 50) {
        self::init();
        
        $logFile = self::$logDir . '/' . strtolower($level) . '.log';
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $file = new SplFileObject($logFile);
        $file->seek(PHP_INT_MAX);
        
        $lastLine = $file->key();
        $startLine = max(0, $lastLine - $lines + 1);
        
        $logs = [];
        $file->seek($startLine);
        
        while (!$file->eof() && count($logs) < $lines) {
            $logs[] = trim($file->fgets());
            $file->next();
        }
        
        return array_filter($logs);
    }
}
?>
