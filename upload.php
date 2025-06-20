<?php
require_once "includes/session_handler.php";
CustomSessionHandler::initialize();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Define upload settings
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB max file size
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);
$uploadError = '';

// Handle direct PHP upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["image"])) {
    $file = $_FILES["image"];
    $response = array();
    
    // Validate file
    if ($file["error"] !== UPLOAD_ERR_OK) {
        $uploadError = "Upload failed with error code: " . $file["error"];
    } elseif ($file["size"] > MAX_FILE_SIZE) {
        $uploadError = "File is too large. Maximum size is " . (MAX_FILE_SIZE / 1024 / 1024) . "MB";
    } elseif (!in_array($file["type"], ALLOWED_TYPES)) {
        $uploadError = "Invalid file type. Allowed types: JPG, JPEG, PNG";
    } else {
        // Create temp directory if it doesn't exist
        $tempDir = "uploads/temp/";
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        
        // Generate unique filename
        $tempName = uniqid('upload_') . '_' . basename($file["name"]);
        $tempPath = $tempDir . $tempName;
        
        if (move_uploaded_file($file["tmp_name"], $tempPath)) {
            // File successfully uploaded, now send to Python server
            $pythonServerUrl = "https://stingray-app-tzszp.ondigitalocean.app/upload";
            
            $postData = array(
                'image' => new CURLFile($tempPath, $file["type"], $file["name"])
            );
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $pythonServerUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($httpCode === 200) {
                $response = json_decode($result, true);
                // Clean up temp file
                unlink($tempPath);
                
                // Forward the results to other.php via POST
                echo "<form id='redirectForm' action='other.php' method='POST'>";
                echo "<input type='hidden' name='is_spliced' value='" . (!empty($response['is_spliced']) ? '1' : '0') . "'>";
                echo "<input type='hidden' name='timestamp' value='" . htmlspecialchars($response['timestamp']) . "'>";
                echo "<input type='hidden' name='original_image' value='" . htmlspecialchars($response['original_image']) . "'>";
                echo "<input type='hidden' name='final_result_image' value='" . htmlspecialchars($response['final_result_image']) . "'>";
                echo "</form>";
                echo "<script>document.getElementById('redirectForm').submit();</script>";
                exit;
            } else {
                $uploadError = "Failed to process image. Server returned code: " . $httpCode;
                // Log the actual response for debugging
                error_log("Python server response: " . $result);
            }
            
            curl_close($ch);
            // Clean up temp file on error
            unlink($tempPath);
        } else {
            $uploadError = "Failed to move uploaded file";
        }
    }
    
    if (!empty($uploadError)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $uploadError]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Image - SpliceNoise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="includes/session_timeout.js"></script>
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
            margin: 2rem auto;
            padding: 1rem;
        }

        .upload-container {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(107, 173, 166, 0.1);
            padding: 2rem;
            max-width: 1100px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }

        .upload-container::before {
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

        .user-info {
            margin-bottom: 1.2rem;
            text-align: center;
        }

        .user-info h3 {
            color: var(--teal-dark);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .user-info p {
            color: var(--gray-blue);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .upload-form {
            text-align: center;
            padding: 2.5rem;
            border: 1px dashed var(--teal-medium);
            border-radius: 10px;
            margin: 1.5rem 0;
            transition: all 0.3s ease;
        }

        .upload-form:hover {
            border-color: var(--teal-accent);
            background-color: var(--teal-light);
        }

        .upload-form input[type="file"] {
            margin: 1.5rem 0;
        }

        .btn {
            background: linear-gradient(45deg, var(--teal-dark), var(--teal-accent));
            color: var(--white);
            padding: 0.6rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 109, 119, 0.2);
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

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
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

        .spinner {
                display: none;
                width: 40px;
                height: 40px;
                margin: 20px auto;
                border: 4px solid var(--teal-light);
                border-top: 4px solid var(--teal-dark);
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }

        @keyframes spin {
           0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="upload-container">
            <div class="action-buttons">
                <a href="upload.php" class="action-btn active">Upload Image</a>
                <a href="view_results.php" class="action-btn">View Results</a>
            </div>

            <div class="user-info">
                <h3>Upload New Image</h3>
                <p>Select an image file to analyze for potential splicing.</p>
            </div>

            <div class="upload-form">
                <form id="uploadForm" onsubmit="handleFormSubmit(event)" enctype="multipart/form-data" method="POST">
                    <input type="file" name="image" accept="image/*" required>
                    <br>
                    <input type="submit" class="btn" id="uploadBtn" value="Upload and Analyze">
                    <div class="spinner" id="loadingSpinner"></div>
                </form>
                <div id="responseContainer" style="margin-top: 20px;"></div>
            </div>
        </div>
    </div>

    <script>
        async function handleFormSubmit(event) {
            event.preventDefault();

            const form = event.target;
            const formData = new FormData(form);
            const uploadBtn = document.getElementById('uploadBtn');
            const spinner = document.getElementById('loadingSpinner');

            // Disable button and show spinner
            uploadBtn.disabled = true;
            spinner.style.display = 'block';

            try {
                const response = await fetch("https://stingray-app-tzszp.ondigitalocean.app/upload", {
                    method: "POST",
                    body: formData,
                });

                if (response.ok) {
                    const result = await response.json();
                    const otherForm = document.createElement("form");
                    otherForm.method = "POST";
                    otherForm.action = "other.php";

                    for (const key in result) {
                        const input = document.createElement("input");
                        input.type = "hidden";
                        input.name = key;
                        input.value = result[key];
                        otherForm.appendChild(input);
                    }

                    document.body.appendChild(otherForm);
                    otherForm.submit();
                } else {
                    alert("Error: Unable to process image");
                    // Re-enable button and hide spinner on error
                    uploadBtn.disabled = false;
                    spinner.style.display = 'none';
                }
            } catch (error) {
                alert(`Error: ${error.message}`);
                // Re-enable button and hide spinner on error
                uploadBtn.disabled = false;
                spinner.style.display = 'none';
            }
        }
    </script>
</body>
</html>
