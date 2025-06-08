<?php
session_start();

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
            margin: 1rem auto;
            padding: 0.8rem;
        }

        .results-container {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(107, 173, 166, 0.1);
            padding: 1.2rem;
            max-width: 1100px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }

        .results-container::before {
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

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 0.8rem;
            margin-bottom: 1rem;
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

        .results-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-top: 1rem;
            max-width: 100%;
            align-items: stretch;
        }

        .image-container {
            text-align: center;
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            height: 100%;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(107, 173, 166, 0.1);
        }

        .image-container h3 {
            color: var(--teal-dark);
            margin-bottom: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .result-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            max-height: 400px;
            object-fit: contain;
        }

        .conclusion {
            margin: 1rem 0;
            padding: 1rem;
            border-radius: 8px;
            background-color: <?php echo $results['is_spliced'] ? 'rgba(220, 53, 69, 0.1)' : 'rgba(40, 167, 69, 0.1)'; ?>;
        }

        .conclusion h3 {
            color: <?php echo $results['is_spliced'] ? '#dc3545' : '#28a745'; ?>;
            margin-bottom: 0.3rem;
            font-size: 1.1rem;
        }

        .conclusion p {
            font-size: 0.9rem;
            color: var(--gray-blue);
        }

        .timestamp {
            color: var(--teal-dark);
            font-size: 0.9rem;
            margin-bottom: 1rem;
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

        .disclaimer {
            margin-top: 2rem;
            padding: 1.5rem;
            background: var(--dark-surface-light);
            border-radius: 8px;
            font-size: 0.9rem;
            color: var(--dark-text-muted);
            line-height: 1.7;
        }

        .important-alert {
            background: rgba(220, 53, 69, 0.1);
            border-left: 4px solid #dc3545;
            padding: 1.2rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
        }

        .important-alert h4 {
            color: #dc3545;
            margin-bottom: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
        }

        .important-alert p {
            color: var(--dark-text);
            margin-bottom: 0 !important;
        }

        .disclaimer h3 {
            color: var(--dark-text);
            margin-bottom: 1rem;
            font-size: 1rem;
            font-weight: 600;
        }

        .disclaimer p {
            margin-bottom: 0.8rem;
        }

        .disclaimer a {
            color: var(--teal-medium);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .disclaimer a:hover {
            color: var(--teal-light);
        }

        .important-note-container {
            background: var(--dark-surface-light);
            border-radius: 12px;
            padding: 1.5rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            border: 1px solid rgba(220, 53, 69, 0.2);
            min-height: 500px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .important-note-container h3 {
            color: #ff4d4d;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .important-note-container p {
            color: var(--dark-text);
            font-size: 0.95rem;
            line-height: 1.8;
            opacity: 0.9;
        }

        @media (max-width: 1024px) {
            .results-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .important-note-container {
                grid-column: span 2;
            }
        }

        @media (max-width: 768px) {
            .results-grid {
                grid-template-columns: 1fr;
            }
            .important-note-container {
                grid-column: span 1;
            }
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
                <div class="important-note-container">
                    <h3>⚠️ Important Note</h3>
                    <p>• This tool is specifically designed to detect image splicing based on inconsistencies in sensor noise levels caused by different ISO settings.</p><br>
                    <p>• Detection results may not be accurate for spliced images that do not have distinct ISO settings between the source images.</p>
                </div>
            </div>

            <div class="disclaimer">
                <h3>About This Tool</h3>
                <p>> <i>SpliceNoise</i> is based on the research paper "Exposing Image splicing with inconsistent sensor noise levels" (accepted by Multimedia Tools and Applications, 2020).</p>
                <p>> The implementation focuses on ISO variations in image splicing detection.</p>
                <p>> The tool employs a noise-based image splicing localization method specifically designed for cases where source images have distinct ISO settings.</p>
                <p>> For more information, please visit:</p>
                <p>• Paper: <a href="https://link.springer.com/article/10.1007/s11042-020-09280-z" target="_blank">https://link.springer.com/article/10.1007/s11042-020-09280-z</a></p>
                <p>• Author: Hui Zeng & Anjie Peng & Xiaodan Lin</p>
            </div>
        </div>
    </div>
</body>
</html> 