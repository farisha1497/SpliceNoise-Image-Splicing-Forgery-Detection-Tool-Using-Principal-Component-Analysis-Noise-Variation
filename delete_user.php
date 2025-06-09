<?php
require_once "includes/session_handler.php";
CustomSessionHandler::initialize();

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || !$_SESSION["is_admin"]){
    header("location: login.php");
    exit;
}

require_once "config/database.php";

$response = array('success' => false, 'message' => '');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    // Don't allow deleting admin accounts
    $check_sql = "SELECT is_admin FROM users WHERE email = ?";
    if ($check_stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if ($row = mysqli_fetch_assoc($check_result)) {
            if ($row['is_admin']) {
                $response['message'] = "Cannot delete admin accounts.";
                echo json_encode($response);
                exit;
            }
        }
        mysqli_stmt_close($check_stmt);
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete user's analysis results first
        $sql = "DELETE ar FROM analysis_results ar 
                INNER JOIN users u ON ar.user_id = u.id 
                WHERE u.email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        
        // Delete user's login attempts
        $sql = "DELETE FROM login_attempts WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        
        // Finally, delete the user
        $sql = "DELETE FROM users WHERE email = ? AND is_admin = 0";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            
            if (mysqli_affected_rows($conn) > 0) {
                mysqli_commit($conn);
                $response['success'] = true;
                $response['message'] = "User deleted successfully.";
            } else {
                throw new Exception("User not found or could not be deleted.");
            }
            mysqli_stmt_close($stmt);
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $response['message'] = $e->getMessage();
    }
} else {
    $response['message'] = "Invalid request.";
}

mysqli_close($conn);
echo json_encode($response);
?> 