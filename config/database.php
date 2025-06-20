<?php
define('DB_SERVER', 'http://db-mysql-sgp1-19615-do-user-23338540-0.j.db.ondigitalocean.com');
define('DB_USERNAME', 'doadmin');
define('DB_PASSWORD', 'AVNS_TzV77Kv8IsYeNTpZ4oa');
define('DB_NAME', 'defaultdb');
define('DB_PORT', 25060);
date_default_timezone_set('Asia/Kuala_Lumpur');
// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);

// Check connection
if($conn === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if(!mysqli_query($conn, $sql)){
    die("Error creating database: " . mysqli_error($conn));
}

// Select the database
mysqli_select_db($conn, DB_NAME);

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    salt VARCHAR(255) NOT NULL,
    verification_token VARCHAR(255) DEFAULT NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expires DATETIME DEFAULT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

if(!mysqli_query($conn, $sql)){
    die("Error creating users table: " . mysqli_error($conn));
}

// Add is_admin column if it doesn't exist
$result = mysqli_query($conn, "DESCRIBE users");
$columns = array();
while($row = mysqli_fetch_assoc($result)) {
    $columns[] = $row['Field'];
}

if (!in_array('is_admin', $columns)) {
    $sql = "ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT FALSE";
    if(!mysqli_query($conn, $sql)){
        die("Error adding is_admin column: " . mysqli_error($conn));
    }
}

// Create default admin account if it doesn't exist
$admin_email = "admin@splicenoise.com";
$check_admin = mysqli_query($conn, "SELECT id FROM users WHERE email = '$admin_email'");

if (mysqli_num_rows($check_admin) == 0) {
    // Generate salt and hash password for admin
    $salt_bytes = random_bytes(32);
    $salt = base64_encode($salt_bytes);
    $password = "Admin@SpliceNoise2025"; // Default admin password
    $salted_password = $password . $salt;
    $hashed_password = password_hash($salted_password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 2
    ]);
    
    $sql = "INSERT INTO users (email, password, salt, email_verified, is_admin) VALUES (?, ?, ?, 1, 1)";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "sss", $admin_email, $hashed_password, $salt);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        error_log("Default admin account created successfully");
    }
}

// Check if verification columns exist and add them if they don't
$result = mysqli_query($conn, "DESCRIBE users");
$columns = array();
while($row = mysqli_fetch_assoc($result)) {
    $columns[] = $row['Field'];
}

// Add salt column if it doesn't exist
if (!in_array('salt', $columns)) {
    // First, add the column as nullable
    $sql = "ALTER TABLE users ADD COLUMN salt VARCHAR(255) DEFAULT NULL";
    if(!mysqli_query($conn, $sql)){
        die("Error adding salt column: " . mysqli_error($conn));
    }
    
    // Generate default salt for existing users
    $sql = "UPDATE users SET salt = ? WHERE salt IS NULL";
    if($stmt = mysqli_prepare($conn, $sql)) {
        $default_salt = bin2hex(random_bytes(32));
        mysqli_stmt_bind_param($stmt, "s", $default_salt);
        if(!mysqli_stmt_execute($stmt)) {
            die("Error updating default salt: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);
    }
    
    // Now make it NOT NULL after setting default values
    $sql = "ALTER TABLE users MODIFY COLUMN salt VARCHAR(255) NOT NULL";
    if(!mysqli_query($conn, $sql)){
        die("Error modifying salt column: " . mysqli_error($conn));
    }
    
    error_log("Salt column added and configured successfully");
}

if (!in_array('verification_token', $columns)) {
    $sql = "ALTER TABLE users ADD COLUMN verification_token VARCHAR(255) DEFAULT NULL";
    if(!mysqli_query($conn, $sql)){
        die("Error adding verification_token column: " . mysqli_error($conn));
    }
}

if (!in_array('email_verified', $columns)) {
    $sql = "ALTER TABLE users ADD COLUMN email_verified BOOLEAN DEFAULT FALSE";
    if(!mysqli_query($conn, $sql)){
        die("Error adding email_verified column: " . mysqli_error($conn));
    }
}

// Add reset token columns if they don't exist
if (!in_array('reset_token', $columns)) {
    $sql = "ALTER TABLE users 
            ADD COLUMN reset_token VARCHAR(255) DEFAULT NULL,
            ADD COLUMN reset_token_expires DATETIME DEFAULT NULL";
    if(!mysqli_query($conn, $sql)){
        die("Error adding reset token columns: " . mysqli_error($conn));
    }
}

// Create results table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS analysis_results (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    result_folder VARCHAR(255) NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_spliced BOOLEAN NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if(!mysqli_query($conn, $sql)){
    die("Error creating analysis_results table: " . mysqli_error($conn));
}

// Create login_attempts table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS login_attempts (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(50),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,
    INDEX idx_ip_time (ip_address, timestamp),
    INDEX idx_email_time (email, timestamp)
)";

if(!mysqli_query($conn, $sql)){
    die("Error creating login_attempts table: " . mysqli_error($conn));
}
?>
