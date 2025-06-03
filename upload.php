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
    <title>Image Splicing Detection</title>
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
        .upload-form {
            text-align: center;
            padding: 20px;
            border: 2px dashed #ccc;
            border-radius: 5px;
            margin: 20px 0;
        }
        .upload-form input[type="file"] {
            margin: 20px 0;
        }
        .upload-form input[type="submit"] {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .upload-form input[type="submit"]:hover {
            background: #45a049;
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
            <h3>Upload New Image</h3>
            <p>Select an image file to analyze for potential splicing.</p>
        </div>

        <div class="upload-form">
            <h2>Upload Image for Splicing Detection</h2>
            <form action="process.php" method="post" enctype="multipart/form-data">
                <p>
                    <input type="file" name="image" accept="image/*" required>
                </p>
                <p>
                    <input type="submit" value="Upload and Analyze">
                </p>
            </form>
        </div>
    </div>
</body>
</html> 