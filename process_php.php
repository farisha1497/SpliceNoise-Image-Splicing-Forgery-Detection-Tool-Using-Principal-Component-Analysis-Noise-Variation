<?php
session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "config/database.php";

// Configuration
$upload_dir = 'uploads';
$results_dir = 'results';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create directories if they don't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
if (!file_exists($results_dir)) {
    mkdir($results_dir, 0777, true);
}

// Function to analyze image and detect splicing
function analyzeImage($image_path, $result_folder) {
    try {
        // Read the input image
        $original = imagecreatefromstring(file_get_contents($image_path));
        if (!$original) {
            throw new Exception("Failed to read the input image.");
        }

        // Save the original image to the results folder
        $original_path = $result_folder . '/original.png';
        imagepng($original, $original_path);

        // Get image dimensions
        $M = imagesy($original);
        $N = imagesx($original);

        // Convert to grayscale if the image is colored
        $spliced = [];
        for ($y = 0; $y < $M; $y++) {
            for ($x = 0; $x < $N; $x++) {
                $rgb = imagecolorat($original, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $spliced[$y][$x] = intval(($r + $g + $b) / 3);
            }
        }

        // Block size
        $B = 64;

        // Ensure dimensions are multiples of block size
        $M = floor($M / $B) * $B;
        $N = floor($N / $B) * $B;
        $spliced = array_slice($spliced, 0, $M);
        foreach ($spliced as $rowKey => $row) {
            $spliced[$rowKey] = array_slice($row, 0, $N);
        }

        // Dummy analysis logic (replace this with real splicing detection logic)
        $result_proposed = array_fill(0, $M / $B, array_fill(0, $N / $B, 1));

        // Create detection result
        $result_img = imagecreatetruecolor($N, $M);
        for ($y = 0; $y < $M; $y++) {
            for ($x = 0; $x < $N; $x++) {
                $gray = $spliced[$y][$x];
                $color = imagecolorallocate($result_img, $gray, $gray, $gray);
                imagesetpixel($result_img, $x, $y, $color);
            }
        }

        // Highlight detected regions in red
        foreach ($result_proposed as $i => $row) {
            foreach ($row as $j => $value) {
                if ($value === 2) {
                    $row_start = $i * $B;
                    $row_end = min(($i + 1) * $B, $M);
                    $col_start = $j * $B;
                    $col_end = min(($j + 1) * $B, $N);

                    for ($y = $row_start; $y < $row_end; $y++) {
                        for ($x = $col_start; $x < $col_end; $x++) {
                            // Make block reddish
                            $rgb = imagecolorat($result_img, $x, $y);
                            $r = ($rgb >> 16) & 0xFF;
                            $g = ($rgb >> 8) & 0xFF;
                            $b = $rgb & 0xFF;

                            $r = min(255, $r + 100);
                            $g = max(0, $g - 50);
                            $b = max(0, $b - 50);

                            $color = imagecolorallocate($result_img, $r, $g, $b);
                            imagesetpixel($result_img, $x, $y, $color);
                        }
                    }
                }
            }
        }

        // Save result image
        $final_result_path = $result_folder . '/final_result.png';
        imagepng($result_img, $final_result_path);

        // Determine if image is spliced
        $is_spliced = false;
        foreach ($result_proposed as $row) {
            if (in_array(2, $row)) {
                $is_spliced = true;
                break;
            }
        }

        // Create results structure
        $result_info = [
            "is_spliced" => $is_spliced,
            "timestamp" => date("Y-m-d H:i:s"),
            "original_image" => 'original.png',
            "final_result_image" => 'final_result.png'
        ];

        // Save results to JSON file
        $results_json = $result_folder . '/analysis_results.json';
        file_put_contents($results_json, json_encode($result_info));

        return $result_info;
    } catch (Exception $e) {
        throw new Exception("Image analysis failed: " . $e->getMessage());
    }
}

// Handle file upload
if ($_FILES["image"]["error"] == UPLOAD_ERR_OK) {
    try {
        $tmp_name = $_FILES["image"]["tmp_name"];
        $name = basename($_FILES["image"]["name"]);
        $timestamp = date('Y-m-d_H-i-s');
        $result_folder = $results_dir . '/' . $timestamp;

        // Create result folder
        if (!mkdir($result_folder, 0777, true)) {
            throw new Exception("Failed to create result folder");
        }

        // Move uploaded file to upload directory
        $upload_path = $upload_dir . '/' . $name;
        if (!move_uploaded_file($tmp_name, $upload_path)) {
            throw new Exception("Failed to move uploaded file");
        }

        // Analyze the image
        $results_data = analyzeImage($upload_path, $result_folder);

        // Store results in database
        $sql = "INSERT INTO analysis_results (user_id, result_folder, is_spliced) VALUES (?, ?, ?)";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "isi", $_SESSION["id"], $timestamp, $results_data["is_spliced"]);

            if(!mysqli_stmt_execute($stmt)){
                throw new Exception("Error saving results to database: " . mysqli_error($conn));
            }

            mysqli_stmt_close($stmt);
        }

        // Success - redirect to results page
        header("Location: view_result.php?folder=" . urlencode($timestamp));
        exit();

    } catch (Exception $e) {
        // Log the error
        error_log("Image processing error: " . $e->getMessage());

        // Display user-friendly error with technical details
        echo "<html><head><title>Processing Error</title>";
        echo "<style>body{font-family:Arial,sans-serif;margin:40px;line-height:1.6}";
        echo ".error{background:#ffebee;padding:20px;border-radius:5px;margin:20px 0}";
        echo ".technical{background:#f5f5f5;padding:20px;border-radius:5px;margin:20px 0;font-family:monospace}";
        echo "</style></head><body>";
        echo "<h2>Error Processing Image</h2>";
        echo "<div class='error'>An error occurred while processing your image. Please try again or contact support if the problem persists.</div>";
        echo "<div class='technical'><strong>Technical Details:</strong><br>" . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<p><a href='upload.php'>← Back to Upload Page</a></p>";
        echo "</body></html>";
    }
} else {
    $upload_errors = array(
        UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
        UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form",
        UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded",
        UPLOAD_ERR_NO_FILE => "No file was uploaded",
        UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder",
        UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
        UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload"
    );

    $error_message = isset($upload_errors[$_FILES["image"]["error"]]) 
        ? $upload_errors[$_FILES["image"]["error"]] 
        : "Unknown upload error";

    echo "<html><head><title>Upload Error</title>";
    echo "<style>body{font-family:Arial,sans-serif;margin:40px;line-height:1.6}";
    echo ".error{background:#ffebee;padding:20px;border-radius:5px;margin:20px 0}</style></head><body>";
    echo "<h2>Upload Error</h2>";
    echo "<div class='error'>" . htmlspecialchars($error_message) . "</div>";
    echo "<p><a href='upload.php'>← Back to Upload Page</a></p>";
    echo "</body></html>";
}
?>
