<?php
session_start();

// Check if the user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";
require_once 'vendor/autoload.php';
require_once 'config/mail_config.php';

$email = $email_err = $success_msg = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter your email address.";
    } else{
        $email = trim($_POST["email"]);
        
        // Check if email exists
        $sql = "SELECT id FROM users WHERE email = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = $email;
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    // Generate reset token
                    $reset_token = bin2hex(random_bytes(32));
                    $reset_token_hash = password_hash($reset_token, PASSWORD_DEFAULT);
                    $reset_token_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Update user with reset token
                    $update_sql = "UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?";
                    if($update_stmt = mysqli_prepare($conn, $update_sql)){
                        mysqli_stmt_bind_param($update_stmt, "sss", $reset_token_hash, $reset_token_expires, $email);
                        
                        if(mysqli_stmt_execute($update_stmt)){
                            // Send reset email
                            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/Exposing-splicing-sensor-noise-master2/reset-password.php?token=" . $reset_token . "&email=" . urlencode($email);
                            
                            $to = $email;
                            $subject = "Password Reset Request - SpliceNoise";
                            $message = "We received a request to reset your password for your SpliceNoise account.\n\n";
                            $message .= "To reset your password, click the link below or copy and paste it into your browser:\n\n";
                            $message .= $reset_link . "\n\n";
                            $message .= "For security reasons, this link will expire in 1 hour.\n\n";
                            $message .= "If you did not request this password reset, please ignore this email and your password will remain unchanged.\n\n";
                            $message .= "For your security:\n";
                            $message .= "- Never share your password with anyone\n";
                            $message .= "- Create a strong, unique password\n";
                            $message .= "- Enable two-factor authentication if available\n\n";
                            $message .= "Best regards,\nThe SpliceNoise Team";

                            if(sendEmail($to, $subject, $message)){
                                $success_msg = "Password reset instructions have been sent to your email. Please check your inbox and spam folder.";
                            } else {
                                $email_err = "Error sending reset email. Please try again later.";
                            }
                        } else {
                            $email_err = "Something went wrong. Please try again later.";
                        }
                        mysqli_stmt_close($update_stmt);
                    }
                } else {
                    $email_err = "No account found with that email address.";
                }
            } else {
                $email_err = "Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - SpliceNoise</title>
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
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: var(--teal-dark);
            margin-bottom: 1rem;
            font-size: 1.8rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--teal-deep);
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid var(--teal-light);
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--teal-accent);
            box-shadow: 0 0 0 3px rgba(72, 181, 181, 0.1);
        }

        .btn {
            background: linear-gradient(45deg, var(--teal-dark), var(--teal-accent));
            color: var(--white);
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 6px;
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
        <p style="color: var(--gray-blue); margin-bottom: 2rem; text-align: center; font-size: 0.95rem;">Enter your email address and we'll send you instructions to reset your password.</p>

        <?php 
        if(!empty($success_msg)){
            echo '<div class="success">' . $success_msg . '</div>';
        }        
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo $email; ?>" placeholder="Enter your email">
                <span class="error"><?php echo $email_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn" value="Send Reset Link">
            </div>
            <div class="login-link">
                <p>Remember your password? <a href="login.php">Login here</a></p>
            </div>
        </form>
    </div>
</body>
</html> 