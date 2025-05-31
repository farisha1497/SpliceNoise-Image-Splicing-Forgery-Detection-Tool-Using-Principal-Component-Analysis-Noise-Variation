<?php
// Configuration
$mcr_path = 'C:/Program Files/MATLAB/MATLAB Runtime/R2024b/runtime/win64'; // Update this path to your MCR installation
$matlab_exe = 'C:/xampp/htdocs/Exposing-splicing-sensor-noise-master2/process_image.exe'; // Using absolute path to the executable
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
        
        // Verify file exists
        if (!file_exists($matlab_exe)) {
            throw new Exception("MATLAB executable not found at: " . $matlab_exe);
        }
        
        // Set environment variables for MCR
        $path = getenv("PATH");
        $new_path = $mcr_path . "/runtime/win64;" . 
                   $mcr_path . "/bin/win64;" . 
                   $mcr_path . "/sys/java/jre/win64/jre/bin/server;" .
                   $mcr_path . "/sys/java/jre/win64/jre/bin;" . $path;
        putenv("PATH=" . $new_path);
        
        // Run MATLAB executable with full error capture
        $command = sprintf('"%s" "%s" "%s" 2>&1', $matlab_exe, $upload_path, $result_folder);
        exec($command, $output, $return_var);
        
        if ($return_var !== 0) {
            $error_msg = implode("\n", $output);
            throw new Exception("MATLAB execution failed: " . $error_msg);
        }
        
        // Verify the results exist
        $results_json = $result_folder . '/analysis_results.json';
        if (!file_exists($results_json)) {
            throw new Exception("Analysis results not generated");
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