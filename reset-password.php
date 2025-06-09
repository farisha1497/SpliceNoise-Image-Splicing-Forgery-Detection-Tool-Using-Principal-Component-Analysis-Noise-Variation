<?php
session_start();

// Check if the user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}

// Check if user is verified through OTP
if(!isset($_SESSION["reset_verified"]) || $_SESSION["reset_verified"] !== true || !isset($_SESSION["reset_email"])){
    header("location: forgot-password.php");
    exit;
}

require_once "config/database.php";

$new_password = $confirm_password = "";
$new_password_err = $confirm_password_err = "";
$message = "";

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate new password
    if(empty(trim($_POST["new_password"]))){
        $new_password_err = "Please enter the new password.";     
    } else {
        $password = trim($_POST["new_password"]);
        $uppercase = preg_match('/[A-Z]/', $password);
        $lowercase = preg_match('/[a-z]/', $password);
        $number    = preg_match('/[0-9]/', $password);
        $symbol    = preg_match('/[!@#$%^&*]/', $password);
        
        if(!$uppercase || !$lowercase || !$number || !$symbol || strlen($password) < 12) {
            $new_password_err = "Password must contain:";
            if(!$uppercase) $new_password_err .= "<br>• At least 1 uppercase letter";
            if(!$lowercase) $new_password_err .= "<br>• At least 1 lowercase letter";
            if(!$number) $new_password_err .= "<br>• At least 1 number";
            if(!$symbol) $new_password_err .= "<br>• At least 1 symbol (!@#$%^&*)";
            if(strlen($password) < 12) $new_password_err .= "<br>• At least 12 characters";
        } else {
            $new_password = $password;
        }
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
        // Generate new secure random salt
        $salt_bytes = random_bytes(32); // 256 bits of entropy
        $new_salt = base64_encode($salt_bytes);
        
        // Update password and salt
        $sql = "UPDATE users SET password = ?, salt = ?, reset_token = NULL, reset_token_expires = NULL WHERE email = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Set parameters with secure hashing
            $salted_password = $new_password . $new_salt;
            $param_password = password_hash($salted_password, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,  // 64MB in KiB
                'time_cost' => 4,        // 4 iterations
                'threads' => 2           // 2 parallel threads
            ]);
            $param_email = $_SESSION["reset_email"];
            
            mysqli_stmt_bind_param($stmt, "sss", $param_password, $new_salt, $param_email);
            
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

        h2 {
            color: var(--teal-deep);
            margin-bottom: 0.75rem;
            font-size: 1.8rem;
            text-align: center;
            font-weight: 700;
            letter-spacing: -0.5px;
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

        .message {
            text-align: center;
            margin-bottom: 2rem;
            padding: 1rem;
            border-radius: 8px;
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
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
        <h2>Create New Password</h2>
        <p style="color: var(--gray-blue); margin-bottom: 2rem; text-align: center; font-size: 0.95rem; font-weight: 600;">Please enter your new password.</p>
        <?php 
        if(!empty($message)){
            echo '<div class="message">' . $message . '</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" id="new_password" placeholder="Enter your new password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $new_password; ?>">
                <span class="error"><?php echo $new_password_err; ?></span>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm your password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
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
                <input type="submit" class="btn" value="Reset Password">
            </div>
        </form>
    </div>

    <script>
        // Password validation
        document.getElementById('new_password').addEventListener('input', function(e) {
            const password = e.target.value;
            
            // Check each requirement
            document.getElementById('uppercase').classList.toggle('valid', /[A-Z]/.test(password));
            document.getElementById('lowercase').classList.toggle('valid', /[a-z]/.test(password));
            document.getElementById('number').classList.toggle('valid', /[0-9]/.test(password));
            document.getElementById('symbol').classList.toggle('valid', /[!@#$%^&*]/.test(password));
            document.getElementById('length').classList.toggle('valid', password.length >= 12);
        });
    </script>
</body>
</html> 