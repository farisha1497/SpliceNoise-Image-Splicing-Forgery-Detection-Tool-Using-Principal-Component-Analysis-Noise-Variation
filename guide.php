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
            --teal-accent: #3B9999;
            
            /* Secondary Colors - Adding Indigo */
            --indigo-dark: #2D3258;
            --indigo-medium: #4A507B;
            --indigo-light: #E8E9F3;
            --teal-deep: #003F47;
            --teal-bright: #008999;
            
            /* Dark Mode Colors */
            --dark-bg: #1A1D2D;
            --dark-surface: #242838;
            --dark-surface-light: #2E324A;
            --dark-text: #E8E9F3;
            --dark-text-muted: #B4B6C5;
            
            /* Accent Colors */
            --purple-accent: #7B61FF;
            --blue-accent: #0EA5E9;
            --green-accent: #10B981;
            --orange-accent: #F59E0B;
            
            /* Neutral Colors */
            --soft-white: #F7FCFD;
            --white: #FFFFFF;
            --light-gray: #F0F0F0;
            --silver: #B8B8B8;
            --gray-blue: #657F87;
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
            background: var(--dark-bg);
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
            margin: 1.5rem auto;
            padding: 1rem;
        }

        .guide-container {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(107, 173, 166, 0.1);
            padding: 2rem;
            position: relative;
            overflow: hidden;
            max-width: 1100px;
            margin: 0 auto;
            background: linear-gradient(135deg, var(--white) 0%, var(--soft-white) 100%);
        }

        .guide-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg,
                rgba(123, 97, 255, 0.05),
                rgba(14, 165, 233, 0.05),
                rgba(16, 185, 129, 0.05)
            );
            z-index: -1;
            filter: blur(20px);
            animation: gradientMove 15s ease infinite;
        }

        .guide-section {
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            position: relative;
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .guide-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg,
                var(--purple-accent),
                var(--blue-accent),
                var(--green-accent)
            );
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }

        .guide-section:hover::before {
            opacity: 1;
        }

        .guide-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .section-title {
            padding: 1.5rem 2rem;
            margin: 0;
            background: linear-gradient(90deg,
                rgba(123, 97, 255, 0.08),
                rgba(14, 165, 233, 0.08)
            );
            border-bottom: 1px solid rgba(123, 97, 255, 0.1);
        }

        .section-content {
            padding: 1.5rem 2rem;
            color: var(--gray-blue);
            font-size: 0.95rem;
            line-height: 1.7;
        }

        .important-note {
            margin: 1.5rem 0;
            background: linear-gradient(135deg,
                rgba(245, 158, 11, 0.08),
                rgba(220, 53, 69, 0.08)
            );
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: 12px;
        }

        .tips-box {
            margin: 1.5rem 0 0.5rem 0;
            border-radius: 12px;
            background: linear-gradient(135deg,
                rgba(123, 97, 255, 0.08),
                rgba(14, 165, 233, 0.08)
            );
            border: 1px solid rgba(123, 97, 255, 0.15);
        }

        .steps-list {
            list-style: none;
            counter-reset: steps;
            padding: 0.5rem 0;
        }

        .step-item {
            background: linear-gradient(90deg,
                rgba(123, 97, 255, 0.03),
                rgba(14, 165, 233, 0.03)
            );
            border: 1px solid rgba(123, 97, 255, 0.1);
            border-radius: 12px;
            padding: 1rem 1.5rem 1rem 3.5rem;
            margin-bottom: 1rem;
            position: relative;
            transition: all 0.3s ease;
        }

        .step-item:last-child {
            margin-bottom: 0;
        }

        .step-item::before {
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 32px;
            height: 32px;
        }

        .step-item:hover::before {
            transform: translateY(-50%) scale(1.1);
        }

        .tips-list {
            padding: 0;
            margin: 0;
        }

        .tip-item {
            padding: 0.8rem 1rem 0.8rem 2rem;
            margin: 0;
            position: relative;
            transition: all 0.3s ease;
        }

        .tip-item:not(:last-child) {
            border-bottom: 1px solid rgba(123, 97, 255, 0.1);
        }

        .tip-item::before {
            left: 0.8rem;
        }

        .tips-title {
            padding: 1rem 1.5rem;
            margin: 0;
            border-bottom: 1px solid rgba(123, 97, 255, 0.1);
            font-size: 1.1rem;
        }

        /* Section-specific styling */
        .what-is .section-title {
            background: linear-gradient(90deg,
                rgba(123, 97, 255, 0.08),
                rgba(14, 165, 233, 0.08)
            );
        }

        .how-to .section-title {
            background: linear-gradient(90deg,
                rgba(14, 165, 233, 0.08),
                rgba(16, 185, 129, 0.08)
            );
        }

        .results .section-title {
            background: linear-gradient(90deg,
                rgba(16, 185, 129, 0.08),
                rgba(245, 158, 11, 0.08)
            );
        }

        /* Fun indicators for sections */
        .what-is .section-title::after {
            content: '🔍';
            margin-left: auto;
            font-size: 1.4rem;
        }

        .how-to .section-title::after {
            content: '🚀';
            margin-left: auto;
            font-size: 1.4rem;
        }

        .results .section-title::after {
            content: '✨';
            margin-left: auto;
            font-size: 1.4rem;
        }

        .guide-container {
            padding: 2.5rem;
            background: linear-gradient(135deg,
                rgba(255, 255, 255, 0.9),
                rgba(247, 252, 253, 0.9)
            );
        }

        .page-title {
            margin-bottom: 3rem;
        }

        /* Enhanced box shadows for depth */
        .guide-section {
            box-shadow: 
                0 4px 20px rgba(0, 0, 0, 0.08),
                0 0 0 1px rgba(123, 97, 255, 0.1);
        }

        /* Smooth transitions */
        .guide-section,
        .step-item,
        .tip-item,
        .important-note {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .what-is::before {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23005761"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/></svg>');
        }

        .how-to::before {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23005761"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14h-2V9h-2V7h4v10z"/></svg>');
        }

        .results::before {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23005761"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14l-5-5 1.41-1.41L12 14.17l4.59-4.58L18 11l-6 6z"/></svg>');
        }

        .important-note h4 {
            color: var(--orange-accent);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .important-note p {
            color: var(--charcoal);
            font-size: 0.9rem;
            margin: 0;
        }

        .tips-box::before {
            background: linear-gradient(45deg, 
                transparent,
                rgba(123, 97, 255, 0.2),
                rgba(14, 165, 233, 0.2),
                transparent
            );
        }

        .page-title {
            text-align: center;
            color: var(--teal-deep);
            font-size: 2.2rem;
            margin-bottom: 2.5rem;
            font-weight: 700;
            position: relative;
            padding-bottom: 1rem;
            background: linear-gradient(90deg, 
                var(--teal-dark),
                var(--purple-accent),
                var(--blue-accent)
            );
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg,
                var(--teal-accent),
                var(--purple-accent),
                var(--blue-accent)
            );
            border-radius: 2px;
        }

        .steps-list {
            list-style: none;
            counter-reset: steps;
        }

        .step-item {
            position: relative;
            padding-left: 2.5rem;
            margin-bottom: 1.2rem;
            counter-increment: steps;
            transition: transform 0.3s ease;
            background: linear-gradient(90deg,
                rgba(123, 97, 255, 0.05),
                rgba(14, 165, 233, 0.05)
            );
            border-radius: 8px;
            padding: 1rem 1rem 1rem 3rem;
        }

        .step-item:hover {
            transform: translateX(10px);
            background: linear-gradient(90deg,
                rgba(123, 97, 255, 0.1),
                rgba(14, 165, 233, 0.1)
            );
        }

        .step-item::before {
            content: counter(steps);
            position: absolute;
            left: 0;
            top: 0;
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, var(--teal-medium), var(--teal-dark));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: 600;
            transition: transform 0.3s ease;
            background: linear-gradient(135deg, var(--blue-accent), var(--purple-accent));
        }

        .step-item:hover::before {
            transform: scale(1.1);
        }

        @media (max-width: 768px) {
            .guide-container {
                margin: 1rem;
                padding: 1rem;
            }

            h1 {
                font-size: 1.6rem;
            }

            .step-title {
                font-size: 0.95rem;
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .guide-section {
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
        }

        .guide-section:nth-child(1) { animation-delay: 0.2s; }
        .guide-section:nth-child(2) { animation-delay: 0.4s; }
        .guide-section:nth-child(3) { animation-delay: 0.6s; }

        /* Rainbow hover effect for steps */
        .step-item:nth-child(1):hover::before { background: linear-gradient(135deg, var(--teal-accent), var(--purple-accent)); }
        .step-item:nth-child(2):hover::before { background: linear-gradient(135deg, var(--purple-accent), var(--blue-accent)); }
        .step-item:nth-child(3):hover::before { background: linear-gradient(135deg, var(--blue-accent), var(--green-accent)); }
        .step-item:nth-child(4):hover::before { background: linear-gradient(135deg, var(--green-accent), var(--orange-accent)); }
        .step-item:nth-child(5):hover::before { background: linear-gradient(135deg, var(--orange-accent), var(--teal-accent)); }

        /* Glowing effect for tips box */
        .tips-box::before {
            background: linear-gradient(45deg, 
                transparent,
                rgba(123, 97, 255, 0.2),
                rgba(14, 165, 233, 0.2),
                transparent
            );
        }

        /* Section hover effects */
        .guide-section:hover .section-title {
            background: linear-gradient(90deg, 
                var(--purple-accent),
                var(--blue-accent),
                var(--green-accent)
            );
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .guide-section:hover .section-title::after {
            background: linear-gradient(90deg,
                var(--purple-accent),
                var(--blue-accent),
                var(--green-accent)
            );
        }

        /* Animated background for the container */
        .guide-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg,
                rgba(123, 97, 255, 0.05),
                rgba(14, 165, 233, 0.05),
                rgba(16, 185, 129, 0.05)
            );
            z-index: -1;
            filter: blur(20px);
            animation: gradientMove 15s ease infinite;
        }

        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Rainbow text effect for tips titles */
        .tips-title {
            background: linear-gradient(90deg,
                var(--purple-accent),
                var(--blue-accent),
                var(--green-accent)
            );
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-weight: 700;
        }

        /* Enhance step items with gradients */
        .step-item {
            background: linear-gradient(90deg,
                rgba(123, 97, 255, 0.05),
                rgba(14, 165, 233, 0.05)
            );
            border-radius: 8px;
            padding: 1rem 1rem 1rem 3rem;
            margin-bottom: 1rem;
        }

        .step-item:hover {
            background: linear-gradient(90deg,
                rgba(123, 97, 255, 0.1),
                rgba(14, 165, 233, 0.1)
            );
        }

        .what-is .section-content {
            border-left: 3px solid var(--purple-accent);
            padding-left: 1rem;
        }

        .how-to .section-content {
            border-left: 3px solid var(--blue-accent);
            padding-left: 1rem;
        }

        .results .section-content {
            border-left: 3px solid var(--green-accent);
            padding-left: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="main-content">
        <div class="guide-container">
            <h1 class="page-title">User Guide</h1>

            <div class="guide-section">
                <h2 class="section-title what-is">What is SpliceNoise?</h2>
                <div class="section-content">
                    <p>SpliceNoise is your digital detective for image authenticity! Using advanced AI and noise pattern analysis, it uncovers hidden image manipulations that might be invisible to the naked eye. Think of it as a forensic tool that examines the unique "fingerprints" left by different camera settings.</p>
                    
                    <div class="important-note">
                        <h4>⚠️ Important Note</h4>
                        <p>This tool specializes in detecting image splicing through ISO noise pattern analysis. Like a detective looking for specific clues, it works best when examining images spliced from sources with different ISO settings.</p>
                    </div>
                </div>
            </div>

            <div class="guide-section">
                <h2 class="section-title how-to">How to Use SpliceNoise</h2>
                <div class="section-content">
                    <ul class="steps-list">
                        <li class="step-item">Join the investigation team - create your account or log in</li>
                        <li class="step-item">Head to the Upload Image section - your analysis command center</li>
                        <li class="step-item">Choose your suspect image for analysis</li>
                        <li class="step-item">Let our AI detective do its work (it's quick but thorough!)</li>
                        <li class="step-item">Review the detailed findings in your analysis report</li>
                    </ul>

                    <div class="tips-box">
                        <h3 class="tips-title">🌟 Power User Tips</h3>
                        <ul class="tips-list">
                            <li class="tip-item">Feed it high-res images - more pixels mean better detection</li>
                            <li class="tip-item">Keep it original - uncompressed images tell the full story</li>
                            <li class="tip-item">Fresh from camera? Perfect! That's what we love</li>
                            <li class="tip-item">EXIF data is like DNA - keep it intact!</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="guide-section">
                <h2 class="section-title results">Understanding Your Results</h2>
                <div class="section-content">
                    <p>Your investigation report includes:</p>
                    <ul class="steps-list">
                        <li class="step-item">The Original Scene: Your image in its submitted form</li>
                        <li class="step-item">The Reveal: Our visualization highlighting potential manipulations</li>
                        <li class="step-item">The Verdict: Clear conclusions about detected splicing</li>
                        <li class="step-item">The Evidence: Marked areas showing suspicious regions</li>
                    </ul>

                    <div class="tips-box">
                        <h3 class="tips-title">🔍 Detective's Notes</h3>
                        <ul class="tips-list">
                            <li class="tip-item">Red zones are your primary suspects - pay extra attention here</li>
                            <li class="tip-item">Brighter red = stronger evidence of manipulation</li>
                            <li class="tip-item">Connect the dots between visual clues and the analysis report</li>
                            <li class="tip-item">Document everything - timestamps and details matter!</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 