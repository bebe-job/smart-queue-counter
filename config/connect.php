<?php
// Database Configuration Credentials
$host = "localhost";
$username = "root";     // Default XAMPP username
$password = "";         // Default XAMPP password (blank)
$dbname = "smart_queue_db";

// Establish MySQLi Connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check Connection Status
if ($conn->connect_error) {
    header('Content-Type: application/json');
    die(json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . $conn->connect_error
    ]));
}

// Set Charset to UTF-8
$conn->set_charset("utf8");
?>