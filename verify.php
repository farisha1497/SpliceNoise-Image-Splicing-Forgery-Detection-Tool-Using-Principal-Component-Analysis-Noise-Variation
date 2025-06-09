<?php
// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/database.php";

$message = "";
$message_type = "";

if(isset($_GET["token"]) && isset($_GET["email"])) {
    $token = trim($_GET["token"]);
    $email = trim($_GET["email"]);
    
    // Verify token
    $sql = "SELECT id, verification_token, email_verified FROM users WHERE email = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        
        if(mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            
            if(mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $id, $stored_token, $email_verified);
                if(mysqli_stmt_fetch($stmt)) {
                    if($email_verified) {
                        $message = "Your email has been verified successfully! You can now login.";
                        $message_type = "success";
                    } else if($stored_token === null) {
                        $message = "Invalid verification token. Token may have expired or already been used.";
                        $message_type = "error";
                    } else if($token === $stored_token) {
                        // Update user as verified
                        $update_sql = "UPDATE users SET email_verified = TRUE, verification_token = NULL WHERE id = ?";
                        if($update_stmt = mysqli_prepare($conn, $update_sql)) {
                            mysqli_stmt_bind_param($update_stmt, "i", $id);
                            
                            if(mysqli_stmt_execute($update_stmt)) {
                                $message = "Your email has been verified successfully! You can now login.";
                                $message_type = "success";
                            } else {
                                $message = "Error verifying email. Please try again later.";
                                $message_type = "error";
                            }
                            mysqli_stmt_close($update_stmt);
                        }
                    } else {
                        $message = "Invalid verification token.";
                        $message_type = "error";
                    }
                }
            } else {
                $message = "Invalid email address.";
                $message_type = "error";
            }
        } else {
            $message = "Oops! Something went wrong. Please try again later.";
            $message_type = "error";
        }
        mysqli_stmt_close($stmt);
    }
} else {
    $message = "Invalid verification link.";
    $message_type = "error";
}

mysqli_close($conn);

// Set page title
$page_title = "Email Verification";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Email Verification - SpliceNoise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --teal-dark: #005761;
            --teal-medium: #6BADA6;
            --teal-light: #E5F2F5;
            --charcoal: #2A2A2A;
            --teal-accent: #3B9999;
            --teal-deep: #003F47;
            --teal-bright: #008999;
            --soft-white: #F7FCFD;
            --gray-blue: #657F87;
            --white: #FFFFFF;
            --light-gray: #F0F0F0;
            --silver: #B8B8B8;
            --success: #28a745;
            --error: #dc3545;
            --info: #17a2b8;
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
            text-align: center;
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
            color: var(--teal-dark);
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .message {
            padding: 1rem;
            border-radius: 6px;
            margin: 1rem 0;
            text-align: center;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .message.success {
            background-color: #f0f9f1;
            color: #28a745;
            border: none;
        }

        .message.error {
            background-color: #fff5f5;
            color: #dc3545;
            border: none;
        }

        .message.info {
            background-color: #f0f8ff;
            color: #17a2b8;
            border: none;
        }

        .btn {
            display: inline-block;
            background: var(--teal-dark);
            color: var(--white);
            padding: 0.8rem 2.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-top: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn:hover {
            background: var(--teal-medium);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 87, 97, 0.15);
        }

        .btn:active {
            transform: translateY(1px);
        }

        @media (max-width: 768px) {
            .container {
                margin: 2rem 1rem;
                padding: 1.5rem;
            }

            h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <h2>Email Verification</h2>
        
        <?php if(!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <a href="login.php" class="btn">Go to Login</a>
    </div>
</body>
</html> 