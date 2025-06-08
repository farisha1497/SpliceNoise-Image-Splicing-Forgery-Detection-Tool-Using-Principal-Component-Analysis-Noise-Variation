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
                            // Get the server protocol and host
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
                            $host = $_SERVER['HTTP_HOST'];
                            // Remove any localhost references and use actual domain
                            $base_url = $protocol . $host;
                            // Create the reset link with proper domain
                            $reset_link = $base_url . "/reset-password.php?token=" . $reset_token . "&email=" . urlencode($email);
                            
                            $to = $email;
                            $subject = "Password Reset Request - SpliceNoise";
                            
                            // Create HTML email body
                            $html_message = '
                            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                                <div style="background: linear-gradient(135deg, #005761, #3B9999); padding: 20px; border-radius: 10px 10px 0 0;">
                                    <h1 style="color: #ffffff; margin: 0; text-align: center;">SpliceNoise</h1>
                                </div>
                                <div style="background: #ffffff; padding: 20px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                                    <p style="color: #333333; font-size: 16px; line-height: 1.6;">Hello,</p>
                                    <p>We received a request to reset your password for your SpliceNoise account.</p>
                                    <p>To reset your password, click the button below:</p>
                                    
                                    <div style="text-align: center; margin: 30px 0;">
                                        <a href="' . $reset_link . '" style="background: linear-gradient(45deg, #005761, #3B9999); color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;">Reset Password</a>
                                    </div>
                                    
                                    <p>Or copy and paste this link into your browser:</p>
                                    <p style="background: #f5f5f5; padding: 10px; border-radius: 5px; word-break: break-all;">' . $reset_link . '</p>
                                    
                                    <p><strong>Important:</strong> This link will expire in 1 hour for security reasons.</p>
                                    
                                    <p>If you did not request this password reset, please ignore this email and your password will remain unchanged.</p>
                                    
                                    <div style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 5px;">
                                        <p style="margin: 0; font-weight: bold;">For your security:</p>
                                        <ul style="margin: 10px 0; padding-left: 20px;">
                                            <li>Never share your password with anyone</li>
                                            <li>Create a strong, unique password</li>
                                            <li>Enable two-factor authentication if available</li>
                                        </ul>
                                    </div>
                                    
                                    <p style="margin-top: 30px;">Best regards,<br>The SpliceNoise Team</p>
                                    
                                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; color: #666666;">
                                        <p style="font-size: 12px;">This is an automated message, please do not reply.</p>
                                    </div>
                                </div>
                            </div>';

                            // Create plain text version
                            $text_message = "We received a request to reset your password for your SpliceNoise account.\n\n";
                            $text_message .= "To reset your password, click the link below or copy and paste it into your browser:\n\n";
                            $text_message .= $reset_link . "\n\n";
                            $text_message .= "For security reasons, this link will expire in 1 hour.\n\n";
                            $text_message .= "If you did not request this password reset, please ignore this email and your password will remain unchanged.\n\n";
                            $text_message .= "For your security:\n";
                            $text_message .= "- Never share your password with anyone\n";
                            $text_message .= "- Create a strong, unique password\n";
                            $text_message .= "- Enable two-factor authentication if available\n\n";
                            $text_message .= "Best regards,\nThe SpliceNoise Team";

                            if(sendEmail($to, $subject, $text_message, $html_message)){
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
            background: #0B1437;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(59, 153, 153, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(74, 80, 123, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, rgba(107, 173, 166, 0.05) 0%, transparent 60%);
            z-index: -1;
        }

        .container {
            max-width: 500px;
            margin: 3rem auto;
            padding: 2rem;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 
                0 10px 30px rgba(0, 87, 97, 0.15),
                0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(107, 173, 166, 0.1);
            position: relative;
            overflow: hidden;
            transform: translateY(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, var(--teal-dark), var(--teal-accent));
        }

        .container:hover {
            transform: translateY(-2px);
            box-shadow: 
                0 15px 35px rgba(0, 87, 97, 0.2),
                0 3px 6px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: var(--teal-dark);
            margin-bottom: 1rem;
            font-size: 1.8rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.3rem;
            color: var(--charcoal);
            font-weight: 500;
            font-size: 0.85rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.65rem 0.8rem;
            border: 2px solid var(--teal-medium);
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            background: var(--soft-white);
            color: var(--charcoal);
            height: 38px;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--teal-accent);
            box-shadow: 0 0 0 3px rgba(59, 153, 153, 0.1);
            background: var(--white);
        }

        .form-group input::placeholder {
            color: var(--gray-blue);
            opacity: 0.8;
        }

        .btn {
            background: var(--teal-dark);
            color: var(--white);
            padding: 0.6rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            width: 100%;
            font-family: 'Poppins', sans-serif;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
            height: 38px;
            box-shadow: 0 2px 4px rgba(0, 87, 97, 0.2);
        }

        .btn:hover {
            background: var(--teal-medium);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 87, 97, 0.3);
        }

        .error {
            color: #c82333;
            font-size: 0.8rem;
            margin-top: 0.3rem;
            padding: 0.3rem;
            border-radius: 4px;
            background-color: rgba(200, 35, 51, 0.1);
            font-weight: 500;
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
            margin-top: 1rem;
            text-align: center;
            color: var(--charcoal);
            font-size: 0.85rem;
        }

        .login-link a {
            color: var(--teal-dark);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
            position: relative;
        }

        .login-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--teal-bright);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .login-link a:hover {
            color: var(--teal-deep);
        }

        .login-link a:hover::after {
            transform: scaleX(1);
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