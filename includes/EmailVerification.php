<?php
class EmailVerification {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function verifyEmail($email, $token) {
        $sql = "SELECT id FROM users WHERE email = ? AND verification_token = ? AND email_verified = 0";
        
        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $email, $token);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    // Update user as verified
                    $update_sql = "UPDATE users SET email_verified = 1, verification_token = NULL WHERE email = ?";
                    if ($update_stmt = mysqli_prepare($this->conn, $update_sql)) {
                        mysqli_stmt_bind_param($update_stmt, "s", $email);
                        $success = mysqli_stmt_execute($update_stmt);
                        mysqli_stmt_close($update_stmt);
                        return $success;
                    }
                }
            }
            mysqli_stmt_close($stmt);
        }
        
        return false;
    }
    
    public function isEmailVerified($email) {
        $sql = "SELECT email_verified FROM users WHERE email = ?";
        
        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_bind_result($stmt, $verified);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);
                return $verified;
            }
            mysqli_stmt_close($stmt);
        }
        
        return false;
    }
    
    public function resendVerificationEmail($email) {
        // Generate new verification token
        $new_token = bin2hex(random_bytes(32));
        
        $sql = "UPDATE users SET verification_token = ? WHERE email = ? AND email_verified = 0";
        
        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $new_token, $email);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                return $new_token;
            }
            mysqli_stmt_close($stmt);
        }
        
        return false;
    }
    
    public function getVerificationStatus($email) {
        $sql = "SELECT email_verified, verification_token FROM users WHERE email = ?";
        
        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);
                return $user;
            }
            mysqli_stmt_close($stmt);
        }
        
        return null;
    }
}
?> 