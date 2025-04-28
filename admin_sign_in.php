<?php
require_once("headers.php");
require_once("db.php");
// session_start();
header('Content-Type: application/json');

// Get the raw POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Check if email and password are received in the JSON payload
if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode(array("status" => "error", "message" => "Email or password not provided."));
    exit();
}

// Get the email and password from the JSON payload
$email = $data['email'];
$password = $data['password'];

// Hash the password using SHA-512
$hashed_password = hash('sha512', $password);



// Prepare and bind
$stmt = $conn->prepare("SELECT email, role FROM pravesh WHERE email = ? AND password = ?");
$stmt->bind_param("ss", $email, $hashed_password);
$stmt->execute();
$stmt->store_result();

// Check if a record is found
if ($stmt->num_rows > 0) {
    // Fetch the user details
    $stmt->bind_result( $username, $role);
    $stmt->fetch();

    // Set session variables
    
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;

    echo json_encode(array("status" => "success", "message" => "User logged in successfully.", "username" => $username, "role" => $role));
} else {
    echo json_encode(array("status" => "error", "message" => "Invalid email or password."));
}

// Close connections
$stmt->close();
$conn->close();
?>
