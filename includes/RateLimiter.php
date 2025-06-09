<?php
class RateLimiter {
    private $conn;
    private $email;
    
    // Rate limit settings
    const MAX_ATTEMPTS = 5;         // Maximum attempts within timeframe
    const TIMEFRAME = 300;          // Timeframe in seconds (5 minutes)
    const LOCKOUT_DURATION = 300;    // Lockout duration in seconds
    
    public function __construct($conn, $ip_address = null, $email = null) {
        $this->conn = $conn;
        $this->email = $email;
    }
    
    public function isAllowed() {
        $this->cleanOldAttempts();
        
        // If no email is provided, allow the attempt
        if (!$this->email) {
            return true;
        }
        
        // Check if email is currently locked out
        if ($this->isLockedOut()) {
            return false;
        }
        
        // Count recent attempts
        $attempts = $this->getRecentAttempts();
        return $attempts < self::MAX_ATTEMPTS;
    }
    
    public function logAttempt($success = false) {
        // Don't log if no email is provided
        if (!$this->email) {
            return;
        }
        
        $sql = "INSERT INTO login_attempts (email, success) VALUES (?, ?)";
        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $this->email, $success);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
    
    public function getRemainingAttempts() {
        if (!$this->email) {
            return self::MAX_ATTEMPTS;
        }
        $attempts = $this->getRecentAttempts();
        return max(0, self::MAX_ATTEMPTS - $attempts);
    }
    
    public function getWaitTime() {
        if (!$this->email) {
            return 0;
        }
        
        // First check if we have enough failed attempts
        $sql = "SELECT COUNT(*) as attempts 
                FROM login_attempts 
                WHERE email = ? 
                AND success = 0 
                AND timestamp > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        
        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            $timeframe = self::TIMEFRAME;
            mysqli_stmt_bind_param($stmt, "si", $this->email, $timeframe);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                if ($row['attempts'] >= self::MAX_ATTEMPTS) {
                    // Get the most recent failed attempt time
                    $sql2 = "SELECT UNIX_TIMESTAMP(timestamp) as last_attempt 
                            FROM login_attempts 
                            WHERE email = ? AND success = 0 
                            ORDER BY timestamp DESC LIMIT 1";
                    
                    if ($stmt2 = mysqli_prepare($this->conn, $sql2)) {
                        mysqli_stmt_bind_param($stmt2, "s", $this->email);
                        mysqli_stmt_execute($stmt2);
                        $result2 = mysqli_stmt_get_result($stmt2);
                        
                        if ($row2 = mysqli_fetch_assoc($result2)) {
                            $last_attempt = (int)$row2['last_attempt'];
                            $time_passed = time() - $last_attempt;
                            $wait_time = self::LOCKOUT_DURATION - $time_passed;
                            return max(0, $wait_time);
                        }
                        mysqli_stmt_close($stmt2);
                    }
                }
            }
            mysqli_stmt_close($stmt);
        }
        return 0;
    }
    
    private function isLockedOut() {
        return $this->getWaitTime() > 0;
    }
    
    private function getRecentAttempts() {
        if (!$this->email) {
            return 0;
        }
        
        $sql = "SELECT COUNT(*) as attempts 
                FROM login_attempts 
                WHERE email = ? 
                AND timestamp > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        
        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            $timeframe = self::TIMEFRAME;
            mysqli_stmt_bind_param($stmt, "si", $this->email, $timeframe);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                return (int)$row['attempts'];
            }
            mysqli_stmt_close($stmt);
        }
        return 0;
    }
    
    private function cleanOldAttempts() {
        $sql = "DELETE FROM login_attempts 
                WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? SECOND)";
        
        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            // Keep records for the longer of TIMEFRAME or LOCKOUT_DURATION
            $cleanup_time = max(self::TIMEFRAME, self::LOCKOUT_DURATION);
            mysqli_stmt_bind_param($stmt, "i", $cleanup_time);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
} 