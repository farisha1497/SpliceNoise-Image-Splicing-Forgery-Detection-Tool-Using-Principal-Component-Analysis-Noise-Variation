<?php
session_start();

// Check if the user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: upload.php");
    exit;
}

require_once "config/database.php";

$email = $password = $confirm_password = "";
$email_err = $password_err = $confirm_password_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
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
                } else{
                    $email = trim($_POST["email"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
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
        $sql = "INSERT INTO users (email, password) VALUES (?, ?)";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "ss", $param_email, $param_password);
            
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            
            if(mysqli_stmt_execute($stmt)){
                header("location: login.php");
            } else{
                echo "Oops! Something went wrong. Please try again later.";
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
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <h2>Create Account</h2>
        <p style="color: var(--gray-blue); margin-bottom: 2rem; text-align: center; font-size: 0.95rem; font-weight: 600;">Join SpliceNoise to start analyzing images.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
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