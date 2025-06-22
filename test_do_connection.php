<?php
// Database credentials
$host = 'db-mysql-sgp1-19615-do-user-23338540-0.j.db.ondigitalocean.com';
$username = 'doadmin';
$password = 'AVNS_wO2qHxKdeeUPd0I3Q07';
$database = 'defaultdb';
$port = 25060;

// Create connection
$conn = new mysqli($host, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

echo "Connection successful!<br>";
echo "Server info: " . $conn->server_info . "<br>";

// List tables
$result = $conn->query("SHOW TABLES");
if ($result) {
    echo "<h3>Tables in database:</h3>";
    echo "<ul>";
    while($row = $result->fetch_array()) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "Error listing tables: " . $conn->error;
}

$conn->close();
?>