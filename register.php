<?php
// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: upload.php");
    exit;
}

require_once "config/database.php";
require_once "config/mail_config.php";
require_once "includes/RateLimiter.php";
require_once "includes/EmailVerification.php";
require_once "config/recaptcha.php";

$email = $password = $confirm_password = "";
$email_err = $password_err = $confirm_password_err = "";
$registration_success = false;

// Initialize rate limiter for registration
$rate_limiter = new RateLimiter($conn, $_SERVER['REMOTE_ADDR']);
$email_verification = new EmailVerification($conn);

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Verify reCAPTCHA first
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    $recaptcha_result = verifyRecaptcha($recaptcha_response);
    
    if (!$recaptcha_result['success'] || $recaptcha_result['score'] < RECAPTCHA_SCORE_THRESHOLD) {
        $email_err = "Invalid request. Please try again.";
    } else {
        // Check if registration attempts are allowed
        if (!$rate_limiter->isAllowed()) {
            $wait_time = $rate_limiter->getWaitTime();
            $email_err = "Too many registration attempts. Please try again after " . ceil($wait_time / 60) . " minutes.";
        } else {
            // Validate email
            if(empty(trim($_POST["email"]))){
                $email_err = "Please enter an email.";
            } else{
                $sql = "SELECT id FROM users WHERE email = ?";
                
                if($stmt = mysqli_prepare($conn, $sql)){
                    mysqli_stmt_bind_param($stmt, "s", $param_email);
                    $param_email = trim($_POST["email"]);
                    
                    if(mysqli_stmt_execute($stmt)){
                        mysqli_stmt_store_result($stmt);
                        
                        if(mysqli_stmt_num_rows($stmt) == 1){
                            $email_err = "This email is already taken.";
                            $rate_limiter->logAttempt(false);
                        } else{
                            $email = trim($_POST["email"]);
                        }
                    } else{
                        echo "Oops! Something went wrong. Please try again later.";
                        $rate_limiter->logAttempt(false);
                    }

                    mysqli_stmt_close($stmt);
                }
            }
            
            // Validate password
            if(empty(trim($_POST["password"]))){
                $password_err = "Please enter a password.";     
            } else {
                $password = trim($_POST["password"]);
                $uppercase = preg_match('/[A-Z]/', $password);
                $lowercase = preg_match('/[a-z]/', $password);
                $number    = preg_match('/[0-9]/', $password);
                $symbol    = preg_match('/[!@#$%^&*]/', $password);
                
                if(!$uppercase || !$lowercase || !$number || !$symbol || strlen($password) < 12) {
                    $password_err = "Password must contain:";
                    if(!$uppercase) $password_err .= "<br>• At least 1 uppercase letter";
                    if(!$lowercase) $password_err .= "<br>• At least 1 lowercase letter";
                    if(!$number) $password_err .= "<br>• At least 1 number";
                    if(!$symbol) $password_err .= "<br>• At least 1 symbol (!@#$%^&*)";
                    if(strlen($password) < 12) $password_err .= "<br>• At least 12 characters";
                }
            }
            
            // Validate confirm password
            if(empty(trim($_POST["confirm_password"]))){
                $confirm_password_err = "Please confirm password.";     
            } else{
                $confirm_password = trim($_POST["confirm_password"]);
                if(empty($password_err) && ($password != $confirm_password)){
                    $confirm_password_err = "Password did not match.";
                }
            }
            
            // Check input errors before inserting in database
            if(empty($email_err) && empty($password_err) && empty($confirm_password_err)){
                // Generate verification token and secure random salt
                $verification_token = bin2hex(random_bytes(32));
                
                // Generate a cryptographically secure random salt
                $salt_bytes = random_bytes(32); // 256 bits of entropy
                $salt = base64_encode($salt_bytes); // More efficient than hex encoding
                
                $sql = "INSERT INTO users (email, password, salt, verification_token) VALUES (?, ?, ?, ?)";
                 
                if($stmt = mysqli_prepare($conn, $sql)){
                    mysqli_stmt_bind_param($stmt, "ssss", $param_email, $param_password, $salt, $verification_token);
                    
                    $param_email = $email;
                    // Create a secure password hash using the random salt
                    $salted_password = $password . $salt;
                    $param_password = password_hash($salted_password, PASSWORD_ARGON2ID, [
                        'memory_cost' => 65536,  // 64MB in KiB
                        'time_cost' => 4,        // 4 iterations
                        'threads' => 2           // 2 parallel threads
                    ]);
                    
                    // For debugging
                    error_log("Registration - Email: " . $param_email);
                    error_log("Generated salt: " . $salt);
                    error_log("Password hash length: " . strlen($param_password));
                    
                    if(mysqli_stmt_execute($stmt)){
                        // Log successful registration
                        $rate_limiter->logAttempt(true);
                        $registration_success = true;
                        
                        // Send verification email
                        $base_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
                        $verification_link = rtrim($base_url, '/') . "/verify.php?email=" . urlencode($email) . "&token=" . urlencode($verification_token);
                        
                        $subject = "Verify Your Email - SpliceNoise";
                        $text_message = "Welcome to SpliceNoise!\n\n";
                        $text_message .= "Please click the following link to verify your email address:\n";
                        $text_message .= $verification_link . "\n\n";
                        $text_message .= "If you did not create this account, please ignore this email.\n";
                        
                        $html_message = '
                        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                            <div style="background: linear-gradient(135deg, #005761, #3B9999); padding: 20px; border-radius: 10px 10px 0 0;">
                                <h1 style="color: #ffffff; margin: 0; text-align: center;">Welcome to SpliceNoise!</h1>
                            </div>
                            <div style="background: #ffffff; padding: 20px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                                <p style="color: #333333; font-size: 16px; line-height: 1.6;">Hello,</p>
                                <p style="color: #333333; font-size: 16px; line-height: 1.6;">Thank you for registering with SpliceNoise. To complete your registration, please verify your email address by clicking the button below:</p>
                                
                                <div style="text-align: center; margin: 30px 0;">
                                    <a href="' . $verification_link . '" style="background-color: #005761; color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">Verify Email Address</a>
                                </div>
                                
                                <p style="color: #666666; font-size: 14px;">If the button doesn\'t work, you can also copy and paste this link into your browser:</p>
                                <p style="color: #666666; font-size: 14px; word-break: break-all;"><a href="' . $verification_link . '">' . $verification_link . '</a></p>
                                
                                <p style="color: #666666; font-size: 14px; margin-top: 30px;">If you did not create this account, please ignore this email.</p>
                            </div>
                        </div>';
                        
                        if(sendEmail($email, $subject, $text_message, $html_message)){
                            $registration_success = true;
                        } else {
                            // If email fails, still allow registration but inform user
                            $registration_success = true;
                            $email_err = "Registration successful but verification email could not be sent. Please contact support.";
                        }
                    } else{
                        echo "Oops! Something went wrong. Please try again later.";
                        $rate_limiter->logAttempt(false);
                    }
                }
            } else {
                // Log failed registration attempt
                $rate_limiter->logAttempt(false);
            }
        }
    }
    
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - SpliceNoise</title>
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
            
            /* Dark Mode Colors */
            --dark-bg: #1A1D2D;
            --dark-surface: #242838;
            --dark-surface-light: #2E324A;
            --dark-text: #E8E9F3;
            --dark-text-muted: #B4B6C5;
            
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

        .header {
            background: var(--dark-surface);
            border-bottom: 1px solid rgba(107, 173, 166, 0.1);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
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
            color: var(--teal-deep);
            margin-bottom: 0.75rem;
            font-size: 1.8rem;
            text-align: center;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        p {
            color: var(--charcoal);
            opacity: 0.85;
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

        .btn:active {
            transform: translateY(1px);
            box-shadow: 0 2px 4px rgba(0, 87, 97, 0.2);
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

        .password-requirements {
            
            padding: 0.75rem;
            border-radius: 6px;
            margin: 0.4rem 0 1rem 0;
            font-size: 0.8rem;
            color: var(--charcoal);
        }

        .password-requirements h4 {
            color: var(--charcoal);
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

        .success-message {
            background-color: rgba(40, 167, 69, 0.1);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
            color: #28a745;
        }
        
        .success-message h3 {
            color: #28a745;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }
        
        .success-message p {
            color: #2d7a39;
            font-size: 0.9rem;
            margin: 0;
        }
    </style>
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo RECAPTCHA_SITE_KEY; ?>"></script>
    <script>
        function onSubmit(e) {
            e.preventDefault();
            grecaptcha.ready(function() {
                grecaptcha.execute('<?php echo RECAPTCHA_SITE_KEY; ?>', {action: 'register'})
                .then(function(token) {
                    // Add the token to the form
                    const tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = 'g-recaptcha-response';
                    tokenInput.value = token;
                    document.getElementById('registerForm').appendChild(tokenInput);
                    
                    // Submit the form
                    document.getElementById('registerForm').submit();
                });
            });
            return false;
        }
    </script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <?php if($registration_success): ?>
            <div class="success-message">
                <h3>Registration Successful!</h3>
                <p>A verification email has been sent to your email address. Please check your inbox and click the verification link to activate your account.</p>
                <div class="login-link" style="margin-top: 20px;">
                    <a href="login.php" class="btn">Proceed to Login</a>
                </div>
            </div>
        <?php else: ?>
            <h2>Create Account</h2>
            <p style="color: var(--gray-blue); margin-bottom: 2rem; text-align: center; font-size: 0.95rem; font-weight: 600;">Join SpliceNoise to start analyzing images.</p>
            <form id="registerForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" onsubmit="return onSubmit(event)">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo $email; ?>" placeholder="Enter your email">
                    <span class="error"><?php echo $email_err; ?></span>
                </div>    
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" id="password" placeholder="Enter your password">
                    <span class="error"><?php echo $password_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm your password">
                    <span class="error"><?php echo $confirm_password_err; ?></span>
                </div>
                <div class="password-requirements">
                    <h4>Password Requirements:</h4>
                    <ul>
                        <li id="uppercase">At least 1 uppercase letter (A-Z)</li>
                        <li id="lowercase">At least 1 lowercase letter (a-z)</li>
                        <li id="number">At least 1 digit (0-9)</li>
                        <li id="symbol">At least 1 symbol (!@#$%^&*)</li>
                        <li id="length">At least 12 characters</li>
                    </ul>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn" value="Create Account">
                </div>
                <div class="login-link">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // Password validation
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            
            // Check each requirement
            document.getElementById('uppercase').classList.toggle('valid', /[A-Z]/.test(password));
            document.getElementById('lowercase').classList.toggle('valid', /[a-z]/.test(password));
            document.getElementById('number').classList.toggle('valid', /[0-9]/.test(password));
            document.getElementById('symbol').classList.toggle('valid', /[!@#$%^&*]/.test(password));
            document.getElementById('length').classList.toggle('valid', password.length >= 12);
        });

        function toggleMenu() {
            const menu = document.getElementById('accountMenu');
            menu.classList.toggle('active');
        }

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('accountMenu');
            const icon = event.target.closest('.account-icon');
            if (!icon && menu.classList.contains('active')) {
                menu.classList.remove('active');
            }
        });
    </script>
</body>
</html>