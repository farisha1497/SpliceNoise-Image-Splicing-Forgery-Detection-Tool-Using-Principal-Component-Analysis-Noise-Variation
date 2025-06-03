<?php
session_start();

// Check if the user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: upload.php");
    exit;
}

require_once "config/database.php";

$email = $password = "";
$email_err = $password_err = $login_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter email.";
    } else{
        $email = trim($_POST["email"]);
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($email_err) && empty($password_err)){
        $sql = "SELECT id, email, password FROM users WHERE email = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = $email;
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    mysqli_stmt_bind_result($stmt, $id, $email, $hashed_password);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            session_start();
                            
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["email"] = $email;
                            
                            header("location: upload.php");
                        } else{
                            $login_err = "Invalid email or password.";
                        }
                    }
                } else{
                    $login_err = "Invalid email or password.";
                }
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
    <title>Login - SpliceNoise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Primary Colors */
            --teal-dark: #006D77;
            --teal-medium: #83C5BE;
            --teal-light: #EDF6F9;
            --charcoal: #333333;
            --teal-accent: #48B5B5;
            
            /* Secondary Colors */
            --teal-deep: #004E57;
            --teal-bright: #00A5B5;
            --soft-white: #FAFEFF;
            --gray-blue: #7A97A0;
            
            /* Neutral Colors */
            --white: #FFFFFF;
            --light-gray: #F5F5F5;
            --silver: #C0C0C0;
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

        .header {
            background: linear-gradient(to right, var(--teal-dark), var(--teal-accent));
            padding: 1.5rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 2px 10px rgba(0, 109, 119, 0.1);
            margin-bottom: 2rem;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--white);
            text-decoration: none;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .nav-buttons {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            text-decoration: none;
            color: var(--teal-light);
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -4px;
            left: 0;
            background-color: var(--white);
            transition: width 0.3s ease;
        }

        .nav-link:hover {
            color: var(--white);
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: var(--teal-dark);
            margin-bottom: 1rem;
            font-size: 2rem;
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

        .register-link {
            margin-top: 1.5rem;
            text-align: center;
            color: var(--gray-blue);
        }

        .register-link a {
            color: var(--teal-bright);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .register-link a:hover {
            color: var(--teal-dark);
        }

        @media (max-width: 768px) {
            .container {
                margin: 1rem;
                padding: 1.5rem;
            }

            .header {
                padding: 1rem;
            }

            .nav-buttons {
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">SpliceNoise</a>
            <div class="nav-buttons">
                <a href="index.php" class="nav-link">Home</a>
                <a href="guide.php" class="nav-link">Guide</a>
                <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                <div class="user-menu">
                    <span class="user-email"><?php echo htmlspecialchars($_SESSION["email"]); ?></span>
                    <div class="account-icon" onclick="toggleMenu()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <div class="account-menu" id="accountMenu">
                            <a href="profile.php" class="menu-item">Profile</a>
                            <a href="settings.php" class="menu-item">Settings</a>
                            <a href="logout.php" class="menu-item logout">Logout</a>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="auth-buttons">
                    <a href="login.php" class="auth-btn login-btn">Login</a>
                    <a href="register.php" class="auth-btn register-btn">Register</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container">
        <h2>Welcome Back</h2>
        <p style="color: var(--gray-blue); margin-bottom: 2rem;">Please enter your credentials to continue.</p>

        <?php 
        if(!empty($login_err)){
            echo '<div class="error">' . $login_err . '</div>';
        }        
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
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