<?php
class CustomSessionHandler {
    const SESSION_TIMEOUT = 180; // 3 minute in seconds
    
    public static function initialize() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        self::checkSessionTimeout();
    }
    
    public static function checkSessionTimeout() {
        if (isset($_SESSION['last_activity'])) {
            $inactive_time = time() - $_SESSION['last_activity'];
            
            if ($inactive_time >= self::SESSION_TIMEOUT) {
                // Session has expired
                session_unset();
                session_destroy();
                
                // Store the timeout message in a temporary session
                session_start();
                $_SESSION['timeout_message'] = "Your session has expired due to inactivity. Please log in again.";
                
                // Redirect to login page
                header("location: login.php");
                exit;
            }
        }
        
        // Update last activity timestamp
        $_SESSION['last_activity'] = time();
    }
    
    public static function getTimeoutMessage() {
        if (isset($_SESSION['timeout_message'])) {
            $message = $_SESSION['timeout_message'];
            unset($_SESSION['timeout_message']);
            return $message;
        }
        return null;
    }
} 