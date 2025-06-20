<?php
require_once "includes/session_handler.php";
CustomSessionHandler::initialize();
date_default_timezone_set('Asia/Kuala_Lumpur');
// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["id"])) {
    header("location: login.php");
    exit;
}

require_once "config/database.php";

// Get data passed from upload.php
$is_spliced = filter_var($_POST['is_spliced'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
$timestamp = $_POST['timestamp'] ?? null;
$original_image = $_POST['original_image'] ?? null;
$final_result_image = $_POST['final_result_image'] ?? null;

// Validate required parameters
if ($is_spliced === null || $timestamp === null || $original_image === null || $final_result_image === null) {
    error_log("Missing required parameters in other.php");
    header("Location: upload.php");
    exit;
}

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
$python_server = "https://stingray-app-tzszp.ondigitalocean.app";

// Variables to track file save status
$original_saved = false;
$final_saved = false;

// Download and save original image
$original_content = file_get_contents($python_server . "/" . ltrim($original_image, "/"));
if ($original_content !== false) {
    $original_saved = file_put_contents($result_folder . "/original.png", $original_content) !== false;
}

// Download and save final result image
$final_content = file_get_contents($python_server . "/" . ltrim($final_result_image, "/"));
if ($final_content !== false) {
    $final_saved = file_put_contents($result_folder . "/final_result.png", $final_content) !== false;
}

// Store results in database if files were saved successfully
if ($original_saved && $final_saved) {
    $sql = "INSERT INTO analysis_results (user_id, result_folder, is_spliced) VALUES (?, ?, ?)";
    if($stmt = mysqli_prepare($conn, $sql)){
        $user_id = $_SESSION["id"];
        mysqli_stmt_bind_param($stmt, "isi", $user_id, $timestamp, $is_spliced);
        
        if(!mysqli_stmt_execute($stmt)){
            error_log("Error saving results to database: " . mysqli_error($conn));
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Save JSON file for results
$result_data = [
    'is_spliced' => $is_spliced,
    'timestamp' => $timestamp,
    'original_image' => 'original.png',
    'final_result_image' => 'final_result.png'
];
file_put_contents($result_folder . "/analysis_results.json", json_encode($result_data));

// Redirect to results page with the folder parameter
header("Location: view_result.php?folder=" . urlencode($timestamp));
exit;
?>
