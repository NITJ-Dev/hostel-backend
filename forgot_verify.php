<?php
require_once("headers.php");
require_once("db.php");
date_default_timezone_set('Asia/Kolkata');


// Get the token from the URL


// Get the posted data
$data = json_decode(file_get_contents("php://input"));

if ($data && isset($data->password, $data->code)) {
    $newPassword = hash('sha512', $data->password);
    $token=$data->code;

    // Verify the token
    $stmt = $conn->prepare("SELECT email FROM password_resets WHERE verification_code = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $email = $row['email'];

        // Update the password in the student_login table
        $stmt = $conn->prepare("UPDATE student_login SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $newPassword, $email);

        if ($stmt->execute()) {
            // Delete the used token
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE verification_code = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();

            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Password reset successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to reset password.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
}
?>
