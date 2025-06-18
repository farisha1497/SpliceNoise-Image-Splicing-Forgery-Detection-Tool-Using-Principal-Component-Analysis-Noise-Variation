<?php
require_once "config/database.php";

// Get data passed from upload.php
$is_spliced = $_POST['is_spliced'] ?? null;
$timestamp = $_POST['timestamp'] ?? null;
$original_image = $_POST['original_image'] ?? null;
$final_result_image = $_POST['final_result_image'] ?? null;

// Save the received data or perform additional actions
$results_dir = "results/";
if (!is_dir($results_dir)) {
    mkdir($results_dir, 0777, true);
}

// Create timestamp folder
$result_folder = $results_dir . $timestamp;
if (!is_dir($result_folder)) {
    mkdir($result_folder, 0777, true);
}

// Copy the images from Python server to local folder
$python_server = "https://urchin-app-oraka.ondigitalocean.app";

// Download and save original image
$original_content = file_get_contents($python_server . "/" . ltrim($original_image, "/"));
if ($original_content !== false) {
    file_put_contents($result_folder . "/original.png", $original_content);
}

// Download and save final result image
$final_content = file_get_contents($python_server . "/" . ltrim($final_result_image, "/"));
if ($final_content !== false) {
    file_put_contents($result_folder . "/final_result.png", $final_content);
}

// Save JSON file for results
$result_data = [
    'is_spliced' => $is_spliced,
    'timestamp' => $timestamp,
    'original_image' => 'original.png',
    'final_result_image' => 'final_result.png'
];
file_put_contents($result_folder . "/analysis_results.json", json_encode($result_data));

// Store results in database
if(isset($_SESSION["id"])) {
    $sql = "INSERT INTO analysis_results (user_id, result_folder, is_spliced) VALUES (?, ?, ?)";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "isi", $_SESSION["id"], $timestamp, $is_spliced);
        
        if(!mysqli_stmt_execute($stmt)){
            error_log("Error saving results to database: " . mysqli_error($conn));
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Redirect to results page with the folder parameter
header("Location: view_result.php?folder=" . urlencode($timestamp));
exit;
?>
