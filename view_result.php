<?php
if (!isset($_GET['folder'])) {
    header('Location: view_results.php');
    exit();
}

$folder = $_GET['folder'];
$results_dir = 'results/' . $folder;
$results_file = $results_dir . '/analysis_results.json';

if (!file_exists($results_file)) {
    echo "Results not found.";
    exit();
}

$results = json_decode(file_get_contents($results_file), true);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detailed Results - Image Splicing Detection</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
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
        .results-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        .result-image {
            max-width: 100%;
            height: auto;
        }
        .conclusion {
            margin-top: 20px;
            padding: 15px;
            background-color: <?php echo $results['is_spliced'] ? '#ffebee' : '#e8f5e9'; ?>;
            border-radius: 5px;
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
        <p>Analysis performed on: <?php echo $results['timestamp']; ?></p>
        
        <div class="conclusion">
            <h3>Conclusion</h3>
            <p>
                <?php if ($results['is_spliced']): ?>
                    This image appears to be spliced. Suspicious regions have been highlighted in red.
                <?php else: ?>
                    No evidence of splicing was detected in this image.
                <?php endif; ?>
            </p>
        </div>
        
        <div class="results-grid">
            <div>
                <h3>Original Image</h3>
                <img class="result-image" src="<?php echo $results_dir . '/' . $results['original_image']; ?>" alt="Original Image">
            </div>
            <div>
                <h3>First Method Result</h3>
                <img class="result-image" src="<?php echo $results_dir . '/' . $results['result1_image']; ?>" alt="First Method Result">
            </div>
            <div>
                <h3>Second Method Result (Proposed)</h3>
                <img class="result-image" src="<?php echo $results_dir . '/' . $results['result2_image']; ?>" alt="Second Method Result">
            </div>
        </div>
    </div>
</body>
</html> 