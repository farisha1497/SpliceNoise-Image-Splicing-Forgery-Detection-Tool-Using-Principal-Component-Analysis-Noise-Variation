<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Guide - SpliceNoise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Primary Colors - Teal & Indigo Palette */
            --teal-dark: #005761;
            --teal-medium: #6BADA6;
            --teal-light: #E5F2F5;
            --charcoal: #2A2A2A;
            --teal-accent: #48cae4;
            
            /* Secondary Colors */
            --purple-medium: #b5179e;
            --pink-accent: #ff006e;
            --orange-accent: #ff9e00;
            
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

        .main-content {
            max-width: 1100px;
            margin: 1rem auto;
            padding: 0.5rem;
        }

        .guide-container {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(107, 173, 166, 0.1);
            padding: 1.2rem;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .guide-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .guide-container::before {
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

        .section {
            margin-bottom: 1.2rem;
        }

        .section:last-child {
            margin-bottom: 0;
        }

        .page-title {
            color: var(--teal-dark);
            text-align: center;
            font-size: 1.6rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .section-title {
            color: var(--teal-dark);
            text-align: center;
            font-size: 1.6rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .content {
            color: var(--charcoal);
            padding: 1rem;
            background: var(--light-gray);
            border-radius: 10px;
            margin-bottom: 0.8rem;
            line-height: 1.5;
        }

        .content p {
            font-size: 0.95rem;
            margin-bottom: 0.8rem;
            color: var(--charcoal);
            text-align: center;
        }

        .content p:last-child {
            margin-bottom: 0;
        }

        .note {
            font-size: 0.85rem;
            color: var(--charcoal);
            background: var(--white);
            padding: 0.8rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .steps, .results-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .step, .result-item {
            background: var(--white);
            padding: 0.8rem;
            margin-bottom: 0.6rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: flex-start;
            transition: transform 0.2s ease;
        }

        .step:hover, .result-item:hover {
            transform: translateX(5px);
        }

        .step:last-child, .result-item:last-child {
            margin-bottom: 0;
        }

        .step-number, .result-icon {
            background: var(--teal-accent);
            color: var(--white);
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 600;
            margin-right: 0.8rem;
            flex-shrink: 0;
        }

        .result-icon {
            font-size: 0.75rem;
        }

        .step-text, .result-text {
            font-size: 0.9rem;
            line-height: 1.4;
            padding-top: 0.2rem;
        }

        .result-item {
            background: linear-gradient(
                to right,
                rgba(72, 202, 228, 0.05),
                rgba(255, 255, 255, 1)
            );
        }

        @media (max-width: 768px) {
            .main-content {
                margin: 0.8rem;
                padding: 0.4rem;
            }

            .guide-container {
                padding: 1rem;
            }

            .page-title {
                font-size: 1.4rem;
                margin-bottom: 0.8rem;
            }

            .section-title {
                font-size: 1.1rem;
            }

            .content {
                padding: 0.8rem;
            }

            .step, .result-item {
                padding: 0.7rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="main-content">
        <div class="guide-container">
            <div class="section">
                <h1 class="section-title">What is SpliceNoise?</h1>
                <div class="content">
                    <p>SpliceNoise is a tool that helps detect if parts of an image have been combined from different sources. It does this by examining the noise patterns that naturally exist in digital photos.</p>
                    <div class="note">
                        The tool works best with original, uncompressed images (JPG or PNG format).
                    </div>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">How to Use SpliceNoise?</h2>
                <div class="content">
                    <ul class="steps">
                        <li class="step">
                            <div class="step-number">1</div>
                            <div class="step-text">Create an account or log in to your existing account</div>
                        </li>
                        <li class="step">
                            <div class="step-number">2</div>
                            <div class="step-text">Click the "Upload Image" button on the navigation menu</div>
                        </li>
                        <li class="step">
                            <div class="step-number">3</div>
                            <div class="step-text">Select your image file (make sure it's JPG or PNG format)</div>
                        </li>
                        <li class="step">
                            <div class="step-number">4</div>
                            <div class="step-text">Wait for the analysis to complete (this usually takes about a minute)</div>
                        </li>
                        <li class="step">
                            <div class="step-number">5</div>
                            <div class="step-text">View your results - red areas in the image indicate potential splicing</div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">Understanding Your Results?</h2>
                <div class="content">
                    <ul class="results-list">
                        <li class="result-item">
                            <div class="result-icon">🎯</div>
                            <div class="result-text">Red highlighted areas show potential image splicing locations</div>
                        </li>
                        <li class="result-item">
                            <div class="result-icon">📊</div>
                            <div class="result-text">Brighter red indicates higher confidence in the detection</div>
                        </li>
                        <li class="result-item">
                            <div class="result-icon">⚠️</div>
                            <div class="result-text">Some textures (like sky or water) might show false positives</div>
                        </li>
                        <li class="result-item">
                            <div class="result-icon">🔍</div>
                            <div class="result-text">Check the edges of highlighted areas carefully - this is where splicing usually occurs</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 