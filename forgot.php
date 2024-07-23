<?php
require_once("headers.php");
require_once("db.php");
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendVerificationEmail($userEmail, $verificationCode) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = 'hostels.nitjalandhar@gmail.com'; // SMTP username
        $mail->Password = 'ddihzzgmnumhsxhz'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('hostels@nitj.ac.in', 'NITJ || Hostels');
        $mail->addAddress($userEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Verification';
        $mail->Body = " This is a system generated email. Please click the link to reset your password: 
        <br><br>https://guesthouseb.nitj.ac.in/NewPassword/$verificationCode";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mail Error: ' . $e->getMessage());
        return false;
    }
}

// Set default response code to 400 (Bad Request)
http_response_code(400);

// Get the posted data
$data = json_decode(file_get_contents("php://input"));

if ($data && isset($data->email)) {
    $email = $data->email;

    // Check if the email exists in student_form table
    $stmt = $conn->prepare("SELECT email FROM student_form WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Check if a verification request is already pending
        $stmt = $conn->prepare("SELECT * FROM password_resets WHERE email = ? AND expires_at > NOW()");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $pendingResult = $stmt->get_result();

        if ($pendingResult->num_rows > 0) {
            http_response_code(409); // Conflict
            echo json_encode(['status' => 'error', 'message' => 'A verification request is already pending. Please check your email.']);
        } else {
            $verificationCode = bin2hex(random_bytes(16)); // Generate a random token
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 hour')); // Token expiry time

            // Store the verification token in the database
            $stmt = $conn->prepare("INSERT INTO password_resets (email, verification_code, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $verificationCode, $expiresAt);
            
            if ($stmt->execute()) {
                if (sendVerificationEmail($email, $verificationCode)) {
                    http_response_code(200);
                    echo json_encode(['status' => 'success', 'message' => 'Verification email sent! Please check your email to reset your password.']);
                } else {
                    http_response_code(500);
                    echo json_encode(['status' => 'error', 'message' => 'Failed to send verification email.']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Failed to store verification token.']);
            }
        }
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Email not found.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
}
?>
