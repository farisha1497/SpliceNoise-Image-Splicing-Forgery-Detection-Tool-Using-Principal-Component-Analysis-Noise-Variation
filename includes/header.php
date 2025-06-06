<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo isset($page_title) ? $page_title . " - SpliceNoise" : "SpliceNoise"; ?></title>
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
        }

        .header {
            background: linear-gradient(to right, #b44fd0, #7b4b82);
            padding: 1rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 20px rgba(90, 42, 106, 0.3);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 40px;
        }

        .logo {
            font-size: 1.6rem;
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
            color: var(--white);
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            font-size: 0.95rem;
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
            color: #ede0f5;
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
            margin-left: 2rem;
            align-items: center;
        }

        .auth-btn {
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .login-btn {
            color: var(--white);
            border: 2px solid rgba(255, 255, 255, 0.3);
            background-color: transparent;
        }

        .register-btn {
            background-color: #ede0f5;
            color: #5A2A6A;
            border: none;
        }

        .auth-btn:hover {
            transform: translateY(-2px);
        }

        .login-btn:hover {
            border-color: #ede0f5;
            background-color: rgba(237, 224, 245, 0.1);
        }

        .register-btn:hover {
            background-color: var(--white);
            color: #5A2A6A;
        }

        .user-email {
            color: var(--white);
            font-size: 0.95rem;
            margin-right: 1rem;
        }

        .logout-btn {
            color: var(--white);
            text-decoration: none;
            padding: 0.5rem 1.5rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: var(--white);
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">SpliceNoise.</a>
            <div class="nav-buttons">
                <a href="index.php" class="nav-link">Home</a>
                <a href="guide.php" class="nav-link">Guide</a>
                <a href="upload.php" class="nav-link">Upload Image</a>
                <a href="view_results.php" class="nav-link">View Results</a>
                <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                    <span class="user-email"><?php echo htmlspecialchars($_SESSION["email"]); ?></span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                <?php else: ?>
                    <div class="auth-buttons">
                        <a href="login.php" class="auth-btn login-btn">Login</a>
                        <a href="register.php" class="auth-btn register-btn">Register</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>
</body>
</html> 