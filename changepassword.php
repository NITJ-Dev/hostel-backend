<?php
require_once("headers.php");
require_once("db.php");
session_start();
header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['rollno'])) {
$rollno = $_SESSION['rollno'];

    echo json_encode(array("status" => "error", "message" => "Try after relogin", "rollno" => $rollno));
    exit();
}

// Get the rollno from session
$rollno = $_SESSION['rollno'];

// Get the raw POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Check if password is received in the JSON payload
if (!isset($data['password'])) {
    echo json_encode(array("status" => "error", "message" => "Password not provided."));
    exit();
}

// Get the new password from the JSON payload
$new_password = $data['password'];

// Hash the new password using SHA-512
$hashed_password = hash('sha512', $new_password);


// Prepare and bind
$stmt = $conn->prepare("UPDATE student_login SET password = ? WHERE rollno = ?");
$stmt->bind_param("ss", $hashed_password, $rollno);

if ($stmt->execute()) {
    echo json_encode(array("status" => "success", "message" => "Password changed successfully."));
} else {
    echo json_encode(array("status" => "error", "message" => "Error updating password: " . $stmt->error));
}

// Close connections
$stmt->close();
$conn->close();
?>
