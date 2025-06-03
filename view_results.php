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
    <title>View Results - Image Splicing Detection</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 5px;
        }
        .nav {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-links {
            display: flex;
            gap: 10px;
        }
        .nav a {
            text-decoration: none;
            padding: 10px;
            background: #f0f0f0;
            border-radius: 3px;
        }
        .nav .logout {
            background: #dc3545;
            color: white;
        }
        .result-list {
            list-style: none;
            padding: 0;
        }
        .result-list li {
            margin: 10px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 3px;
            border-left: 4px solid #4CAF50;
        }
        .result-list li.spliced {
            border-left-color: #dc3545;
        }
        .user-info {
            margin-bottom: 20px;
            padding: 10px;
            background: #e9ecef;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="nav">
        <div class="nav-links">
            <a href="upload.php">Upload Image</a>
            <a href="view_results.php">View Results</a>
        </div>
        <div class="nav-links">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION["email"]); ?></span>
            <a href="logout.php" class="logout">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="user-info">
            <h3>Your Analysis History</h3>
            <p>Total analyses: <?php echo count($results); ?></p>
        </div>

        <h2>Analysis Results</h2>
        <?php if(empty($results)): ?>
            <p>No analysis results found. <a href="upload.php">Upload an image</a> to get started.</p>
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
</body>
</html> 