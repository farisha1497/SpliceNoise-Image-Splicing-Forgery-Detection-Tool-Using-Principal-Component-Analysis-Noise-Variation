<?php
require_once "includes/session_handler.php";
CustomSessionHandler::initialize();

// Check if this is an AJAX request for session timeout
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Check if this is a timeout action from POST data
$isTimeout = false;
if ($isAjax) {
    $data = json_decode(file_get_contents('php://input'), true);
    $isTimeout = isset($data['action']) && $data['action'] === 'timeout';
}

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// If this is an AJAX timeout request, send JSON response
if ($isAjax && $isTimeout) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Session expired']);
    exit;
}

// For regular logout, redirect to login page
header("location: login.php");
exit;
?> 