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
            /* Primary Colors - Teal & Indigo Palette */
            --teal-dark: #005761;
            --teal-medium: #6BADA6;
            --teal-light: #E5F2F5;
            --charcoal: #2A2A2A;
            --teal-accent: #3B9999;
            
            /* Secondary Colors - Adding Indigo */
            --indigo-dark: #2D3258;
            --indigo-medium: #4A507B;
            --indigo-light: #E8E9F3;
            --teal-deep: #003F47;
            --teal-bright: #008999;
            
            /* Dark Mode Colors */
            --dark-bg: #1A1D2D;
            --dark-surface: #242838;
            --dark-surface-light: #2E324A;
            --dark-text: #E8E9F3;
            --dark-text-muted: #B4B6C5;
            
            /* Neutral Colors */
            --soft-white: #F7FCFD;
            --white: #FFFFFF;
            --light-gray: #F0F0F0;
            --silver: #B8B8B8;
            --gray-blue: #657F87;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--dark-text);
            background: var(--dark-bg);
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
            padding: 1rem 2rem;
            border-bottom: 1px solid rgba(107, 173, 166, 0.1);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
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
            color: var(--teal-medium);
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
            color: var(--dark-text-muted);
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
            background: var(--teal-medium);
            transition: width 0.3s ease;
        }

        .nav-link:hover {
            color: var(--teal-medium);
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .main-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 6rem 2rem;
            text-align: center;
            position: relative;
        }

        h1 {
            font-size: 4.5rem;
            margin-bottom: 2rem;
            background: linear-gradient(45deg, var(--teal-medium), var(--indigo-light));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            line-height: 1.2;
            letter-spacing: 1px;
            font-weight: 700;
            animation: fadeInDown 0.8s ease-out;
            text-shadow: 0 2px 10px rgba(107, 173, 166, 0.2);
        }

        .description {
            font-size: 1.1rem;
            color: var(--dark-text-muted);
            margin-bottom: 1.5rem;
            line-height: 1.8;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            font-weight: 500;
            animation: fadeIn 0.8s ease-out 0.2s both;
        }

        .tagline {
            font-style: italic;
            color: var(--teal-medium);
            margin: 2rem 0;
            font-size: 1.4rem;
            opacity: 0.9;
            font-weight: 600;
            letter-spacing: 0.5px;
            animation: fadeIn 0.8s ease-out 0.4s both;
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
            animation: fadeInUp 0.8s ease-out 0.6s both;
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
                rgba(107, 173, 166, 0.2),
                transparent
            );
            transition: 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--teal-dark), var(--indigo-medium));
            color: var(--white);
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 6px 20px rgba(107, 173, 166, 0.2);
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
            margin-left: 2rem;
            align-items: center;
        }

        .auth-btn {
            padding: 0.6rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .login-btn {
            background: rgba(107, 173, 166, 0.1);
            color: var(--dark-text);
            border: 2px solid rgba(107, 173, 166, 0.2);
        }

        .register-btn {
            background-color: var(--dark-surface-light);
            color: var(--teal-medium);
            border: none;
            margin-left: 1rem;
        }

        .auth-btn:hover {
            transform: translateY(-2px);
        }

        .login-btn:hover {
            background: rgba(107, 173, 166, 0.15);
            border-color: var(--teal-medium);
        }

        .register-btn:hover {
            background-color: var(--dark-surface);
            color: var(--teal-light);
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
            font-size: 0.95rem;
            font-weight: 1rem;
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
            background-color: var(--dark-surface);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            padding: 0.5rem;
            min-width: 150px;
            display: none;
            border: 1px solid var(--dark-surface-light);
        }

        .account-menu.active {
            display: block;
        }

        .menu-item {
            display: block;
            padding: 0.7rem 1rem;
            color: var(--dark-text);
            text-decoration: none;
            font-size: 0.9rem;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .menu-item:hover {
            background: var(--dark-surface-light);
            color: var(--teal-medium);
        }

        .menu-item.logout {
            color: #dc3545;
            border-top: 1px solid var(--dark-surface-light);
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
            animation: fadeIn 0.8s ease-out 0.8s both;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--dark-surface-light);
            transition: all 0.3s ease;
            opacity: 0.5;
        }

        .dot.active {
            background: var(--teal-medium);
            transform: scale(1.2);
            opacity: 1;
        }

        .logout-btn {
            color: var(--white);
            text-decoration: none;
            padding: 0.5rem 1.5rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            background: transparent;
        }

        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: var(--white);
        }

        /* Add animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                filter: blur(3px);
            }
            to {
                opacity: 1;
                filter: blur(0);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
                filter: blur(5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
                filter: blur(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
                filter: blur(5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
                filter: blur(0);
            }
        }

        /* Background decoration */
        .bg-decoration {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: -1;
            opacity: 0.1;
        }

        .bg-decoration::before,
        .bg-decoration::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
        }

        .bg-decoration::before {
            background: radial-gradient(circle, var(--teal-dark), transparent);
            top: -100px;
            right: -100px;
            opacity: 0.15;
        }

        .bg-decoration::after {
            background: radial-gradient(circle, var(--indigo-medium), transparent);
            bottom: -100px;
            left: -100px;
            opacity: 0.15;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 3.5rem;
            }

            .description {
                font-size: 1rem;
                padding: 0 1rem;
            }

            .tagline {
                font-size: 1.2rem;
            }

            .main-content {
                padding: 4rem 1rem;
            }

            .btn {
                padding: 1rem 2.5rem;
            }
        }

        /* Enhanced animations for dark mode */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
                filter: blur(5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
                filter: blur(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
                filter: blur(5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
                filter: blur(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                filter: blur(3px);
            }
            to {
                opacity: 1;
                filter: blur(0);
            }
        }

        /* Add subtle glow effect to buttons */
        .btn:focus {
            box-shadow: 0 0 0 3px rgba(107, 173, 166, 0.3);
        }

        /* Add smooth transition for all interactive elements */
        .btn,
        .nav-link,
        .menu-item,
        .auth-btn {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="main-content">
        <div class="bg-decoration"></div>
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
</body>
</html> 