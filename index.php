<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>SpliceNoise - Image Splicing Detection Tool</title>
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
                radial-gradient(circle at 20% 20%, rgba(0, 109, 119, 0.03) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(131, 197, 190, 0.03) 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, rgba(72, 181, 181, 0.03) 0%, transparent 60%);
            z-index: -1;
        }

        .header {
            background: linear-gradient(to right, var(--teal-dark), var(--teal-accent));
            padding: 1rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 2px 10px rgba(0, 109, 119, 0.1);
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
            color: var(--teal-light);
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
            color: var(--white);
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .main-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 4rem 2rem;
            text-align: center;
        }

        h1 {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(45deg, var(--teal-dark), var(--teal-accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            line-height: 1.2;
            letter-spacing: 1px;
            font-weight: 700;
        }

        .description {
            font-size: 1.0rem;
            color: var(--teal-deep);
            margin-bottom: 2rem;
            line-height: 1.6;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            font-weight: 500;
        }

        .tagline {
            font-style: italic;
            color: var(--teal-bright);
            margin-top: 1.5rem;
            font-size: 1.3rem;
            opacity: 0.95;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .btn {
            display: inline-block;
            padding: 1rem 3rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
            font-size: 1.1rem;
            margin-top: 2rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                120deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );
            transition: 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--teal-dark), var(--teal-accent));
            color: var(--white);
            border: none;
            box-shadow: 0 4px 15px rgba(0, 109, 119, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 109, 119, 0.3);
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
            background-color: var(--teal-light);
            color: var(--teal-dark);
            border: none;
        }

        .auth-btn:hover {
            transform: translateY(-2px);
        }

        .login-btn:hover {
            border-color: var(--teal-light);
            background-color: rgba(237, 246, 249, 0.1);
        }

        .register-btn:hover {
            background-color: var(--white);
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-left: 2rem;
            position: relative;
        }

        .user-email {
            color: var(--white);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .account-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: var(--teal-light);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .account-icon:hover {
            background-color: var(--white);
            color: var(--teal-dark);
        }

        .account-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 0.5rem;
            min-width: 150px;
            display: none;
        }

        .account-menu.active {
            display: block;
        }

        .menu-item {
            display: block;
            padding: 0.7rem 1rem;
            color: var(--charcoal);
            text-decoration: none;
            font-size: 0.9rem;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .menu-item:hover {
            background-color: var(--teal-light);
            color: var(--teal-dark);
        }

        .menu-item.logout {
            color: #dc3545;
            border-top: 1px solid var(--light-gray);
            margin-top: 0.5rem;
        }

        .menu-item.logout:hover {
            background-color: #dc3545;
            color: var(--white);
        }

        .dots {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 4rem;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--teal-medium);
            transition: all 0.3s ease;
            opacity: 0.5;
        }

        .dot.active {
            background-color: var(--teal-dark);
            transform: scale(1.2);
            opacity: 1;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 3rem;
            }

            .description {
                font-size: 0.9rem;
                padding: 0 1rem;
            }

            .tagline {
                font-size: 1.2rem;
            }

            .nav-buttons {
                gap: 1rem;
            }

            .auth-buttons {
                flex-wrap: wrap;
                justify-content: center;
            }

            .main-content {
                padding: 4rem 1rem;
            }

            .btn {
                padding: 1rem 2.5rem;
                font-size: 1.1rem;
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

    <div class="main-content">
        <h1>SpliceNoise</h1>
        <p class="description">Check if your images have been manipulated through splicing detection. Our tool analyzes noise patterns to identify edited or combined image regions.</p>
        <p class="tagline">"Unmasking Image Truths, One Pixel at a Time."</p>
        <a href="upload.php" class="btn btn-primary">Upload Image</a>
        <div class="dots">
            <span class="dot active"></span>
            <span class="dot"></span>
            <span class="dot"></span>
            <span class="dot"></span>
        </div>
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