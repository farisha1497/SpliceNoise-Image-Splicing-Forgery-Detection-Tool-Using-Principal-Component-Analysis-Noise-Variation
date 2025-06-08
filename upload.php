<?php
session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Image - SpliceNoise</title>
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
            --purple-medium: #b5179e;
            --pink-accent: #ff006e;
            
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
            color: var(--dark-text);
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

        .container {
            max-width: 1100px;
            margin: 2rem auto;
            padding: 1rem;
        }

        .upload-container {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(107, 173, 166, 0.1);
            padding: 2rem;
            max-width: 1100px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }

        .upload-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg,
                var(--teal-accent),
                var(--purple-medium),
                var(--pink-accent)
            );
            opacity: 0.7;
        }

        .user-info {
            margin-bottom: 1.2rem;
            text-align: center;
        }

        .user-info h3 {
            color: var(--teal-dark);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .user-info p {
            color: var(--gray-blue);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .upload-form {
            text-align: center;
            padding: 2.5rem;
            border: 1px dashed var(--teal-medium);
            border-radius: 10px;
            margin: 1.5rem 0;
            transition: all 0.3s ease;
        }

        .upload-form:hover {
            border-color: var(--teal-accent);
            background-color: var(--teal-light);
        }

        .upload-form input[type="file"] {
            margin: 1.5rem 0;
        }

        .btn {
            background: linear-gradient(45deg, var(--teal-dark), var(--teal-accent));
            color: var(--white);
            padding: 0.6rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 109, 119, 0.2);
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

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
        }

        .action-btn {
            text-decoration: none;
            color: var(--teal-dark);
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background: var(--teal-light);
        }

        .action-btn:hover {
            background: var(--teal-medium);
            color: var(--white);
        }

        .action-btn.active {
            background: var(--teal-dark);
            color: var(--white);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="upload-container">
            <div class="action-buttons">
                <a href="upload.php" class="action-btn active">Upload Image</a>
                <a href="view_results.php" class="action-btn">View Results</a>
            </div>

            <div class="user-info">
                <h3>Upload New Image</h3>
                <p>Select an image file to analyze for potential splicing.</p>
            </div>

            <div class="upload-form">
                <form action="process.php" method="post" enctype="multipart/form-data">
                    <input type="file" name="image" accept="image/*" required>
                    <br>
                    <input type="submit" class="btn" value="Upload and Analyze">
                </form>
            </div>
        </div>
    </div>
</body>
</html> 