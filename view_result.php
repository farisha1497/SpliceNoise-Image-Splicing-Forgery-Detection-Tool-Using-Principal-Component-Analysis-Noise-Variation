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
    <title>Detailed Results - SpliceNoise</title>
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
            max-width: 1000px;
            margin: 4rem auto;
            padding: 2rem;
        }

        .results-container {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
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

        .results-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-top: 2rem;
        }

        .result-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .conclusion {
            margin: 2rem 0;
            padding: 1.5rem;
            border-radius: 8px;
            background-color: <?php echo $results['is_spliced'] ? 'rgba(220, 53, 69, 0.1)' : 'rgba(40, 167, 69, 0.1)'; ?>;
        }

        .conclusion h3 {
            color: <?php echo $results['is_spliced'] ? '#dc3545' : '#28a745'; ?>;
            margin-bottom: 0.5rem;
        }

        .image-container {
            text-align: center;
        }

        .image-container h3 {
            color: var(--teal-dark);
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .timestamp {
            color: var(--teal-dark);
            font-size: 1rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
            text-align: center;
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
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="results-container">
            <div class="action-buttons">
                <a href="upload.php" class="action-btn">Upload Image</a>
                <a href="view_results.php" class="action-btn">View Results</a>
            </div>

            <div class="timestamp">
                Analysis performed on: <?php echo $results['timestamp']; ?>
            </div>
            
            <div class="conclusion">
                <h3>Analysis Conclusion</h3>
                <p>
                    <?php if ($results['is_spliced']): ?>
                        This image appears to be spliced. Suspicious regions have been highlighted in red.
                    <?php else: ?>
                        No evidence of splicing was detected in this image.
                    <?php endif; ?>
                </p>
            </div>
            
            <div class="results-grid">
                <div class="image-container">
                    <h3>Original Image</h3>
                    <img class="result-image" src="<?php echo $results_dir . '/' . $results['original_image']; ?>" alt="Original Image">
                </div>
                <div class="image-container">
                    <h3>Detection Result</h3>
                    <img class="result-image" src="<?php echo $results_dir . '/' . $results['final_result_image']; ?>" alt="Detection Result">
                </div>
            </div>
        </div>
    </div>
</body>
</html> 