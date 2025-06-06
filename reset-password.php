<?php
session_start();

// Check if the user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

$new_password = $confirm_password = "";
$new_password_err = $confirm_password_err = "";
$token = $email = $message = "";
$is_valid = false;

// Check if token and email are provided in URL
if(isset($_GET["token"]) && isset($_GET["email"])) {
    $token = trim($_GET["token"]);
    $email = trim($_GET["email"]);
    
    // Verify token validity
    $sql = "SELECT reset_token, reset_token_expires FROM users WHERE email = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        
        if(mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            
            if(mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $stored_token, $token_expires);
                if(mysqli_stmt_fetch($stmt)) {
                    // Check if token is expired
                    if(strtotime($token_expires) >= time()) {
                        // Verify token
                        if(password_verify($token, $stored_token)) {
                            $is_valid = true;
                        } else {
                            $message = "Invalid reset token. Please request a new password reset link.";
                        }
                    } else {
                        $message = "Reset token has expired. Please request a new password reset.";
                    }
                }
            } else {
                $message = "Invalid email address. Please check your reset link.";
            }
        } else {
            $message = "Oops! Something went wrong. Please try again later.";
            error_log("MySQL Error in reset-password.php: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);
    }
} else {
    $message = "Invalid request. Please use the reset link sent to your email.";
}

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST" && $is_valid){
    // Validate new password
    if(empty(trim($_POST["new_password"]))){
        $new_password_err = "Please enter the new password.";     
    } elseif(strlen(trim($_POST["new_password"])) < 6){
        $new_password_err = "Password must have at least 6 characters.";
    } else{
        $new_password = trim($_POST["new_password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm the password.";
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($new_password_err) && ($new_password != $confirm_password)){
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Check input errors before updating the database
    if(empty($new_password_err) && empty($confirm_password_err)){
        // Update password
        $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE email = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Set parameters
            $param_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            mysqli_stmt_bind_param($stmt, "ss", $param_password, $email);
            
            // Attempt to execute
            if(mysqli_stmt_execute($stmt)){
                // Password updated successfully. Destroy the session and redirect to login page
                session_destroy();
                header("location: login.php?reset=success");
                exit();
            } else{
                $message = "Oops! Something went wrong. Please try again later.";
                error_log("MySQL Error in reset-password.php password update: " . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Close database connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - SpliceNoise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Primary Colors - Darker Teal Palette */
            --teal-dark: #005761;
            --teal-medium: #6BADA6;
            --teal-light: #E5F2F5;
            --charcoal: #2A2A2A;
            --teal-accent: #3B9999;
            
            /* Secondary Colors */
            --teal-deep: #003F47;
            --teal-bright: #008999;
            --soft-white: #F7FCFD;
            --gray-blue: #657F87;
            
            /* Neutral Colors */
            --white: #FFFFFF;
            --light-gray: #F0F0F0;
            --silver: #B8B8B8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--charcoal);
            background: linear-gradient(135deg, var(--teal-light) 0%, var(--soft-white) 100%);
            min-height: 100vh;
        }

        .container {
            max-width: 400px;
            margin: 4rem auto;
            padding: 2rem;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(107, 173, 166, 0.1);
        }

        h2 {
            color: var(--teal-dark);
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            text-align: center;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--teal-deep);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid var(--teal-light);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--teal-accent);
            box-shadow: 0 0 0 3px rgba(59, 153, 153, 0.1);
        }

        .btn {
            background: linear-gradient(45deg, var(--teal-dark), var(--teal-accent));
            color: var(--white);
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
            font-family: 'Poppins', sans-serif;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 109, 119, 0.2);
        }

        .error {
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            padding: 0.5rem;
            background-color: rgba(220, 53, 69, 0.1);
            border-radius: 4px;
        }

        .success {
            color: #28a745;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            text-align: center;
            padding: 0.5rem;
            background-color: rgba(40, 167, 69, 0.1);
            border-radius: 4px;
        }

        .message {
            text-align: center;
            margin-bottom: 2rem;
            padding: 1rem;
            border-radius: 8px;
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .login-link {
            margin-top: 1.5rem;
            text-align: center;
            color: var(--gray-blue);
        }

        .login-link a {
            color: var(--teal-bright);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: var(--teal-dark);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <h2>Reset Password</h2>
        
        <?php 
        if(!empty($message)){
            echo '<div class="message">' . $message . '</div>';
        }
        ?>

        <?php if($is_valid): ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?token=" . $token . "&email=" . urlencode($email); ?>" method="post">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $new_password; ?>">
                    <span class="error"><?php echo $new_password_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                    <span class="error"><?php echo $confirm_password_err; ?></span>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn" value="Reset Password">
                </div>
            </form>
        <?php endif; ?>

        <div class="login-link">
            <p>Remember your password? <a href="login.php">Login here</a></p>
        </div>
    </div>
</body>
</html> 