<?php
session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "config/database.php";

// Get user's results from database
$sql = "SELECT * FROM analysis_results WHERE user_id = ? ORDER BY timestamp DESC";
$results = array();

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)){
            $results[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Results - SpliceNoise</title>
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

        .container {
            max-width: 800px;
            margin: 4rem auto;
            padding: 2rem;
        }

        .results-container {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }

        .user-info {
            margin-bottom: 2rem;
            text-align: center;
        }

        .user-info h3 {
            color: var(--teal-dark);
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .user-info p {
            color: var(--gray-blue);
            font-size: 0.95rem;
        }

        .result-list {
            list-style: none;
            padding: 0;
            max-width: 600px;
            margin: 0 auto;
        }

        .result-list li {
            background: var(--white);
            border-radius: 8px;
            padding: 1.2rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            border: 2px solid var(--teal-light);
            position: relative;
        }

        .result-list li::before {
            content: '';
            position: absolute;
            left: -2px;
            top: 0;
            bottom: 0;
            width: 6px;
            background: var(--teal-medium);
            border-radius: 4px 0 0 4px;
        }

        .result-list li:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 109, 119, 0.15);
            border-color: var(--teal-medium);
        }

        .result-list li.spliced {
            border-color: rgba(220, 53, 69, 0.3);
        }

        .result-list li.spliced::before {
            background: #dc3545;
        }

        .result-list li p {
            margin-bottom: 0.6rem;
            color: var(--gray-blue);
            font-size: 0.95rem;
        }

        .result-list li p:first-child {
            color: var(--teal-dark);
            font-weight: 600;
            margin-bottom: 0.8rem;
        }

        .result-list li a {
            color: var(--teal-bright);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            display: inline-block;
            padding: 0.4rem 0;
            font-size: 0.95rem;
        }

        .result-list li a:hover {
            color: var(--teal-dark);
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

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--gray-blue);
        }

        .empty-state a {
            color: var(--teal-bright);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .empty-state a:hover {
            color: var(--teal-dark);
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .action-btn {
            text-decoration: none;
            color: var(--teal-dark);
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 500;
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
        <div class="results-container">
            <div class="action-buttons">
                <a href="upload.php" class="action-btn">Upload Image</a>
                <a href="view_results.php" class="action-btn active">View Results</a>
            </div>

            <div class="user-info">
                <h3>Your Analysis History</h3>
                <p>Total analyses: <?php echo count($results); ?></p>
            </div>

            <?php if(empty($results)): ?>
                <div class="empty-state">
                    <p>No analysis results found. <a href="upload.php">Upload an image</a> to get started.</p>
                </div>
            <?php else: ?>
                <ul class="result-list">
                    <?php foreach ($results as $result): ?>
                        <li class="<?php echo $result['is_spliced'] ? 'spliced' : ''; ?>">
                            <p>Analysis from: <?php echo $result['timestamp']; ?></p>
                            <p>
                                Status: 
                                <?php if($result['is_spliced']): ?>
                                    <strong style="color: #dc3545;">Splicing Detected</strong>
                                <?php else: ?>
                                    <strong style="color: #28a745;">No Splicing Detected</strong>
                                <?php endif; ?>
                            </p>
                            <p>
                                <a href="view_result.php?folder=<?php echo urlencode($result['result_folder']); ?>">
                                    View Detailed Results
                                </a>
                            </p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 