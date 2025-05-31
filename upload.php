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
        }
        .nav a {
            text-decoration: none;
            padding: 10px;
            background: #f0f0f0;
            margin-right: 10px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="nav">
        <a href="upload.php">Upload Image</a>
        <a href="view_results.php">View Results</a>
    </div>
    
    <div class="container">
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
</body>
</html> 