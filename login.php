<?php
require_once "includes/session_handler.php";
CustomSessionHandler::initialize();

// Check if the user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: upload.php");
    exit;
}

require_once "config/database.php";
require_once "includes/RateLimiter.php";
require_once "includes/RateLimitDisplay.php";
require_once "config/recaptcha.php";

$email = $password = "";
$email_err = $password_err = $login_err = "";

// Initialize rate limiter
$rate_limiter = new RateLimiter($conn, null, isset($_POST["email"]) ? $_POST["email"] : null);

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Verify reCAPTCHA first
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    $recaptcha_result = verifyRecaptcha($recaptcha_response);
    
    if (!$recaptcha_result['success'] || $recaptcha_result['score'] < RECAPTCHA_SCORE_THRESHOLD) {
        $login_err = "Invalid request. Please try again.";
    } else {
        // Check if user is allowed to attempt login
        if (!$rate_limiter->isAllowed()) {
            $login_err = RateLimitDisplay::getStatusMessage($rate_limiter);
        } else {
            // Validate email
            if(empty(trim($_POST["email"]))){
                $email_err = "Please enter email.";
            } else{
                $email = trim($_POST["email"]);
                // Update rate limiter with email
                $rate_limiter = new RateLimiter($conn, null, $email);
            }
            
            // Validate password
            if(empty(trim($_POST["password"]))){
                $password_err = "Please enter your password.";
            } else{
                $password = trim($_POST["password"]);
            }
            
            // Validate credentials
            if(empty($email_err) && empty($password_err)){
                $sql = "SELECT id, email, password, salt, email_verified FROM users WHERE email = ?";
                
                if($stmt = mysqli_prepare($conn, $sql)){
                    mysqli_stmt_bind_param($stmt, "s", $param_email);
                    $param_email = $email;
                    
                    if(mysqli_stmt_execute($stmt)){
                        mysqli_stmt_store_result($stmt);
                        
                        if(mysqli_stmt_num_rows($stmt) == 1){
                            mysqli_stmt_bind_result($stmt, $id, $email, $hashed_password, $salt, $email_verified);
                            if(mysqli_stmt_fetch($stmt)){
                                // Create salted password for verification
                                $salted_password = $password . $salt;
                                
                                // Verify password with current hash algorithm
                                if(password_verify($salted_password, $hashed_password)){
                                    if($email_verified){
                                        // Log successful attempt
                                        $rate_limiter->logAttempt(true);
                                        
                                        // Check if password needs rehash
                                        if (password_needs_rehash($hashed_password, PASSWORD_ARGON2ID, [
                                            'memory_cost' => 65536,
                                            'time_cost' => 4,
                                            'threads' => 2
                                        ])) {
                                            // Generate new salt and rehash
                                            $new_salt_bytes = random_bytes(32);
                                            $new_salt = base64_encode($new_salt_bytes);
                                            $new_salted_password = $password . $new_salt;
                                            $new_hash = password_hash($new_salted_password, PASSWORD_ARGON2ID, [
                                                'memory_cost' => 65536,
                                                'time_cost' => 4,
                                                'threads' => 2
                                            ]);
                                            
                                            // Update password and salt
                                            $update_sql = "UPDATE users SET password = ?, salt = ? WHERE id = ?";
                                            if($update_stmt = mysqli_prepare($conn, $update_sql)) {
                                                mysqli_stmt_bind_param($update_stmt, "ssi", $new_hash, $new_salt, $id);
                                                mysqli_stmt_execute($update_stmt);
                                                mysqli_stmt_close($update_stmt);
                                            }
                                        }
                                        
                                        // Start session and set session variables
                                        session_start();
                                        $_SESSION["loggedin"] = true;
                                        $_SESSION["id"] = $id;
                                        $_SESSION["email"] = $email;
                                        $_SESSION["last_activity"] = time();
                                        
                                        // Check if user is admin and set admin flag
                                        $sql = "SELECT is_admin FROM users WHERE id = ?";
                                        if ($admin_stmt = mysqli_prepare($conn, $sql)) {
                                            mysqli_stmt_bind_param($admin_stmt, "i", $id);
                                            mysqli_stmt_execute($admin_stmt);
                                            $admin_result = mysqli_stmt_get_result($admin_stmt);
                                            if ($admin_row = mysqli_fetch_assoc($admin_result)) {
                                                $_SESSION["is_admin"] = (bool)$admin_row['is_admin'];
                                            }
                                            mysqli_stmt_close($admin_stmt);
                                        }
                                        
                                        // Redirect based on user role
                                        if (isset($_SESSION["is_admin"]) && $_SESSION["is_admin"]) {
                                            header("location: admin.php");
                                        } else {
                                            header("location: upload.php");
                                        }
                                        exit();
                                    } else {
                                        $login_err = "Please verify your email address before logging in. Check your inbox for the verification link.";
                                    }
                                } else {
                                    // Log failed attempt
                                    $rate_limiter->logAttempt(false);
                                    $login_err = RateLimitDisplay::getStatusMessage($rate_limiter);
                                }
                            }
                        } else {
                            // Log failed attempt
                            $rate_limiter->logAttempt(false);
                            $login_err = RateLimitDisplay::getStatusMessage($rate_limiter);
                        }
                    } else {
                        $login_err = "Oops! Something went wrong. Please try again later.";
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
    mysqli_close($conn);
}

// Get session timeout message if it exists
$timeout_message = CustomSessionHandler::getTimeoutMessage();
if ($timeout_message) {
    $login_err = '<div class="rate-limit-error">' . $timeout_message . '</div>';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - SpliceNoise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php echo RateLimitDisplay::addStyles(); ?>
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
            background: linear-gradient(45deg, var(--teal-dark), var(--teal-accent));
            color: var(--white);
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 50px;
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
            height: 45px;
            box-shadow: 0 4px 15px rgba(0, 109, 119, 0.2);
        }

        .btn:hover {
            background: var(--teal-medium);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 87, 97, 0.3);
        }

        .btn:active {
            transform: translateY(1px);
            box-shadow: 0 2px 10px rgba(0, 109, 119, 0.2);
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

        .register-link {
            margin-top: 1rem;
            text-align: center;
            color: var(--charcoal);
            font-size: 0.85rem;
        }

        .register-link a {
            color: var(--teal-dark);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
            position: relative;
        }

        .register-link a::after {
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

        .register-link a:hover {
            color: var(--teal-deep);
        }

        .register-link a:hover::after {
            transform: scaleX(1);
        }

        .forgot-password {
            text-align: right;
            margin: -0.25rem 0 0.75rem;
        }

        .forgot-password a {
            color: var(--teal-bright);
            text-decoration: none;
            font-size: 0.8rem;
            transition: color 0.3s ease;
            font-weight: 500;
        }

        .forgot-password a:hover {
            color: var(--teal-dark);
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
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo RECAPTCHA_SITE_KEY; ?>"></script>
    <script>
        function onSubmit(e) {
            e.preventDefault();
            grecaptcha.ready(function() {
                grecaptcha.execute('<?php echo RECAPTCHA_SITE_KEY; ?>', {action: 'login'})
                .then(function(token) {
                    // Add the token to the form
                    const tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = 'g-recaptcha-response';
                    tokenInput.value = token;
                    document.getElementById('loginForm').appendChild(tokenInput);
                    
                    // Submit the form
                    document.getElementById('loginForm').submit();
                });
            });
            return false;
        }
    </script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <h2>Welcome Back!</h2>
        <p style="color: var(--gray-blue); margin-bottom: 2rem; text-align: center; font-size: 0.95rem; font-weight: 600;">Please enter your credentials to continue.</p>

        <?php 
        if(!empty($login_err)){
            echo '<div class="error">' . $login_err . '</div>';
        }        
        ?>

        <form id="loginForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" onsubmit="return onSubmit(event)">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo $email; ?>" placeholder="Enter your email">
                <span class="error"><?php echo $email_err; ?></span>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password">
                <span class="error"><?php echo $password_err; ?></span>
            </div>
            <div class="forgot-password">
                <a href="forgot-password.php">Forgot Password?</a>
            </div>
            <div class="form-group">
                <input type="submit" class="btn" value="Login">
            </div>
            <div class="register-link">
                <p>Don't have an account? <a href="register.php">Sign up now</a></p>
            </div>
        </form>
    </div>

    <script>
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