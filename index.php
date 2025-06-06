<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>SpliceNoise - Image Splicing Detection Tool</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        /* Move all other styles to style.css */
    </style>
</head>
<body>
    <div class="background-shapes">
        <div class="shape-1"></div>
        <div class="shape-2"></div>
        <div class="shape-3"></div>
    </div>
    <div class="grid-pattern"></div>
    <div class="glow-dots">
        <div class="glow-dot"></div>
        <div class="glow-dot"></div>
        <div class="glow-dot"></div>
        <div class="glow-dot"></div>
        <div class="glow-dot"></div>
    </div>
    
    <?php include 'includes/header.php'; ?>

    <div class="main-content">
        <h1>SpliceNoise</h1>
        <div class="description">
            Check if your images have been manipulated through splicing detection. Our tool analyzes noise patterns to identify edited or combined image regions.
        </div>
        <p class="tagline">"Unmasking Image Truths, One Pixel at a Time."</p>
        <a href="upload.php" class="btn btn-primary">Upload Image</a>
        <div class="dots">
            <span class="dot active"></span>
            <span class="dot active"></span>
            <span class="dot active"></span>
            <span class="dot active"></span>
        </div>
    </div>
</body>
</html> 