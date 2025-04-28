<?php
require_once("headers.php");
require_once("db.php");

// Set default response code to 400 (Bad Request)
http_response_code(400);

$data = json_decode(file_get_contents("php://input"));

if ($data && isset($data->verificationCode)) {
    $verificationCode = $data->verificationCode;

    $stmt = $conn->prepare("SELECT email, timestamp  FROM student_login WHERE verification_code = ? AND is_verified = 0");
    $stmt->bind_param("s", $verificationCode);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($email, $timestamp);
        $stmt->fetch();

        $currentTime = time();
        $tokenTime = strtotime($timestamp);
        $expiryTime = 24 * 60 * 60; // 24 hours

        if (($currentTime - $tokenTime) < $expiryTime) {
            $updateStmt = $conn->prepare("UPDATE student_login SET is_verified = 1 WHERE email = ?");
            $updateStmt->bind_param("s", $email);
            if ($updateStmt->execute()) {
                // Set success response code to 200 (OK)
                http_response_code(200);
                echo json_encode(['status' => 'success', 'message' => 'Email verified successfully!']);
            } else {
                // Set internal server error response code to 500 (Internal Server Error)
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Failed to verify email.']);
            }
            $updateStmt->close();
        } else {
            // Set conflict response code to 409 (Conflict)
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Verification code has expired.']);
        }
    } else {
        // Set not found response code to 404 (Not Found)
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired verification code.']);
    }

    $stmt->close();
    $conn->close();
} else {
    // Set bad request response code to 400 (Bad Request) for no verification code provided
    echo json_encode(['status' => 'error', 'message' => 'No verification code provided.']);
}
?>
