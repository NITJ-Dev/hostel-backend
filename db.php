<?php
// Prevent direct access
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    http_response_code(403);
    exit("Access denied.");
}

$isDev = true; // change to true on local/dev machine

if ($isDev) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php_errors.log'); // Ensure it's writable

// Database config
$host = "localhost";
$user = "u267843737_hostel"; 
$user_password = "Hostel@123"; 
$dbname = "nitjhosteldb"; 

// Connect to database
$conn = mysqli_connect($host, $user, $user_password, $dbname);

// Check connection
if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    http_response_code(500); // Send internal server error
    echo json_encode([
        "success" => false,
        "message" => "Internal server error."
    ]);
    exit;
}
?>
