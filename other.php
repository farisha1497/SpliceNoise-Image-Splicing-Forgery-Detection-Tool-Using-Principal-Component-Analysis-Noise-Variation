<?php
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

// Save JSON file for results
$result_data = [
    'is_spliced' => $is_spliced,
    'timestamp' => $timestamp,
    'original_image' => $original_image,
    'final_result_image' => $final_result_image,
];
file_put_contents($results_dir . "result_{$timestamp}.json", json_encode($result_data));

// Redirect to another page for displaying results
header("Location: display_results.php?timestamp={$timestamp}");
exit;
?>
