<?php
$results_dir = 'results';

// Get all result folders
$folders = array_filter(glob($results_dir . '/*'), 'is_dir');
rsort($folders); // Sort by newest first
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
        }
        .nav a {
            text-decoration: none;
            padding: 10px;
            background: #f0f0f0;
            margin-right: 10px;
            border-radius: 3px;
        }
        .result-list {
            list-style: none;
            padding: 0;
        }
        .result-list li {
            margin: 10px 0;
            padding: 10px;
            background: #f9f9f9;
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
        <h2>Analysis Results</h2>
        <ul class="result-list">
            <?php foreach ($folders as $folder): ?>
                <?php
                $timestamp = basename($folder);
                $results_file = $folder . '/analysis_results.json';
                if (file_exists($results_file)) {
                    $results = json_decode(file_get_contents($results_file), true);
                ?>
                <li>
                    <p>Analysis from: <?php echo $results['timestamp']; ?></p>
                    <p>
                        <a href="view_result.php?folder=<?php echo urlencode($timestamp); ?>">
                            View Detailed Results
                        </a>
                    </p>
                </li>
                <?php } ?>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html> 