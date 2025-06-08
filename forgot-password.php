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

$email = $email_err = $success_msg = $otp = $otp_err = "";
$show_otp_form = false;

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["verify_email"])) {
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
                        // Generate OTP
                        $otp_code = sprintf("%06d", mt_rand(0, 999999));
                        $otp_expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                        $otp_hash = password_hash($otp_code, PASSWORD_DEFAULT);
                        
                        // Update user with OTP
                        $update_sql = "UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?";
                        if($update_stmt = mysqli_prepare($conn, $update_sql)){
                            mysqli_stmt_bind_param($update_stmt, "sss", $otp_hash, $otp_expires, $email);
                            
                            if(mysqli_stmt_execute($update_stmt)){
                                // Send OTP email
                                $to = $email;
                                $subject = "Password Reset OTP - SpliceNoise";
                                
                                // Create HTML email body
                                $html_message = '
                                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                                    <div style="background: linear-gradient(135deg, #005761, #3B9999); padding: 20px; border-radius: 10px 10px 0 0;">
                                        <h1 style="color: #ffffff; margin: 0; text-align: center;">SpliceNoise</h1>
                                    </div>
                                    <div style="background: #ffffff; padding: 20px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                                        <p style="color: #333333; font-size: 16px; line-height: 1.6;">Hello,</p>
                                        <p>We received a request to reset your password for your SpliceNoise account.</p>
                                        <p>Your OTP code is:</p>
                                        
                                        <div style="text-align: center; margin: 30px 0;">
                                            <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; font-size: 24px; letter-spacing: 5px; font-weight: bold;">' . $otp_code . '</div>
                                        </div>
                                        
                                        <p><strong>Important:</strong> This OTP will expire in 15 minutes for security reasons.</p>
                                        
                                        <p>If you did not request this password reset, please ignore this email and your password will remain unchanged.</p>
                                        
                                        <div style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 5px;">
                                            <p style="margin: 0; font-weight: bold;">For your security:</p>
                                            <ul style="margin: 10px 0; padding-left: 20px;">
                                                <li>Never share your OTP with anyone</li>
                                                <li>Our team will never ask for your OTP</li>
                                                <li>Make sure to use a strong password when resetting</li>
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
                                $text_message .= "Your OTP code is: " . $otp_code . "\n\n";
                                $text_message .= "This OTP will expire in 15 minutes.\n\n";
                                $text_message .= "If you did not request this password reset, please ignore this email.\n\n";
                                $text_message .= "For your security:\n";
                                $text_message .= "- Never share your OTP with anyone\n";
                                $text_message .= "- Our team will never ask for your OTP\n";
                                $text_message .= "- Make sure to use a strong password when resetting\n\n";
                                $text_message .= "Best regards,\nThe SpliceNoise Team";

                                if(sendEmail($to, $subject, $text_message, $html_message)){
                                    $_SESSION['reset_email'] = $email;
                                    $success_msg = "OTP has been sent to your email. Please check your inbox and spam folder.";
                                    $show_otp_form = true;
                                } else {
                                    $email_err = "Error sending OTP email. Please try again later.";
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
    } elseif(isset($_POST["verify_otp"])) {
        // Validate OTP
        if(empty(trim($_POST["otp"]))){
            $otp_err = "Please enter the OTP.";
        } else {
            $otp = trim($_POST["otp"]);
            $email = $_SESSION['reset_email'] ?? '';
            
            if(empty($email)) {
                $otp_err = "Session expired. Please try again.";
            } else {
                // Verify OTP
                $sql = "SELECT reset_token, reset_token_expires FROM users WHERE email = ?";
                if($stmt = mysqli_prepare($conn, $sql)){
                    mysqli_stmt_bind_param($stmt, "s", $email);
                    
                    if(mysqli_stmt_execute($stmt)){
                        mysqli_stmt_store_result($stmt);
                        
                        if(mysqli_stmt_num_rows($stmt) == 1){
                            mysqli_stmt_bind_result($stmt, $stored_token, $token_expires);
                            if(mysqli_stmt_fetch($stmt)){
                                if(strtotime($token_expires) >= time()){
                                    if(password_verify($otp, $stored_token)){
                                        // OTP is valid, redirect to password reset page
                                        $_SESSION['reset_verified'] = true;
                                        header("location: reset-password.php");
                                        exit();
                                    } else {
                                        $otp_err = "Invalid OTP code.";
                                        $show_otp_form = true;
                                    }
                                } else {
                                    $otp_err = "OTP has expired. Please request a new one.";
                                }
                            }
                        } else {
                            $otp_err = "Invalid request. Please try again.";
                        }
                    } else {
                        $otp_err = "Something went wrong. Please try again later.";
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }
        $show_otp_form = true;
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
            margin-bottom: 0.8rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.25rem;
            color: var(--charcoal);
            font-weight: 500;
            font-size: 0.85rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 2px solid var(--teal-medium);
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            background: var(--soft-white);
            color: var(--charcoal);
            height: 36px;
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
            margin-top: 0.25rem;
            padding: 0.4rem;
            border-radius: 4px;
            background-color: rgba(200, 35, 51, 0.1);
            font-weight: 500;
            line-height: 1.4;
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

        .otp-input {
            letter-spacing: 8px;
            font-size: 1.2rem;
            text-align: center;
            font-weight: 600;
        }

        .resend-link {
            text-align: center;
            margin-top: 1rem;
        }

        .resend-link a {
            color: var(--teal-bright);
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.3s ease;
        }

        .resend-link a:hover {
            color: var(--teal-dark);
        }

        .password-requirements {
            background: var(--dark-surface-light);
            padding: 0.75rem;
            border-radius: 6px;
            margin: 0.4rem 0 1rem 0;
            font-size: 0.8rem;
            color: var(--dark-text-muted);
        }

        .form-group + .password-requirements {
            margin-top: -0.4rem;
        }

        .password-requirements + .form-group {
            margin-top: 1rem;
        }

        .password-requirements h4 {
            color: var(--dark-text);
            margin-bottom: 0.3rem;
            font-size: 0.85rem;
        }

        .password-requirements ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .password-requirements li {
            margin: 0.15rem 0;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            line-height: 1.2;
        }

        .password-requirements li::before {
            content: '×';
            color: #dc3545;
            font-weight: bold;
            min-width: 12px;
            text-align: center;
        }

        .password-requirements li.valid::before {
            content: '✓';
            color: #28a745;
        }

        @media (max-width: 768px) {
            .container {
                margin: 1.5rem 1rem;
                padding: 1.75rem;
                max-width: 340px;
            }

            h2 {
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <h2>Reset Password</h2>
        <p style="color: var(--gray-blue); margin-bottom: 2rem; text-align: center; font-size: 0.95rem;">
            <?php echo $show_otp_form ? "Enter the OTP code sent to your email." : "Enter your email address and we'll send you an OTP code to reset your password."; ?>
        </p>

        <?php 
        if(!empty($success_msg)){
            echo '<div class="success">' . $success_msg . '</div>';
        }        
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <?php if(!$show_otp_form): ?>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo $email; ?>" placeholder="Enter your email">
                    <span class="error"><?php echo $email_err; ?></span>
                </div>
                <div class="form-group">
                    <input type="submit" name="verify_email" class="btn" value="Send OTP">
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label>OTP Code</label>
                    <input type="text" name="otp" class="otp-input" maxlength="6" placeholder="Enter OTP" pattern="[0-9]{6}" title="Please enter 6 digits">
                    <span class="error"><?php echo $otp_err; ?></span>
                </div>
                <div class="form-group">
                    <input type="submit" name="verify_otp" class="btn" value="Verify OTP">
                </div>
                <div class="resend-link">
                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">Resend OTP</a>
                </div>
            <?php endif; ?>
            <div class="login-link">
                <p>Remember your password? <a href="login.php">Login here</a></p>
            </div>
        </form>
    </div>

    <script>
        // Auto-format OTP input
        const otpInput = document.querySelector('.otp-input');
        if(otpInput) {
            otpInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
            });
        }
    </script>
</body>
</html> 