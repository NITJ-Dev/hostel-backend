<?php
require_once("headers.php");
require_once("db.php");
require 'vendor/autoload.php'; // Load PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendVerificationEmail($userEmail, $verificationCode) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Set the SMTP server to send through
        $mail->SMTPAuth = true;
        $mail->Username = 'hostels@nitj.ac.in'; // SMTP username
        $mail->Password = 'juszznxasmrurfsl'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('hostels@nitj.ac.in', 'NITJ || Hostels');
        $mail->addAddress($userEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'NITJ Hostel Allotment Email Verification';
        $mail->Body = "This is a system generated email. Please click the link to verify your email: 
        <br><br>http://localhost:8080//Verify/$verificationCode";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mail Error: ' . $e->getMessage());
        return false;
    }
}

function is_allowed_rollno($conn, $rollno) {
    $stmt_roll_check = $conn->prepare("SELECT rollno FROM allowed_rollno WHERE rollno = ?");
    $stmt_roll_check->bind_param("s", $rollno);
    $stmt_roll_check->execute();
    $rollno_result = $stmt_roll_check->get_result();
    
    if ($rollno_result->num_rows > 0) {
        $fetched_data = $rollno_result->fetch_assoc();
        $rollno_from_db = $fetched_data['rollno'];
        
        return $rollno_from_db == $rollno;
    } else {
        return false;
    }
}

function isAlreadyRegistered($conn, $rollno, $email) {
    $stmt_check = $conn->prepare("SELECT rollno FROM student_login WHERE rollno = ? OR email = ?");
    $stmt_check->bind_param("ss", $rollno, $email);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    return $result_check->num_rows > 0;
}

function registerUser($conn, $rollno, $email, $password) {
    $verificationCode = bin2hex(random_bytes(16)); // Generate a random token
    $hashedPassword = hash('sha512', $password);
    $isVerified = 0;

    $stmt = $conn->prepare("INSERT INTO student_login (email, rollno, password, verification_code, is_verified) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $email, $rollno, $hashedPassword, $verificationCode, $isVerified);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return sendVerificationEmail($email, $verificationCode);
    } else {
        $stmt->close();
        $conn->close();
        return false;
    }
}

// Set default response code to 400 (Bad Request)
http_response_code(400);

// Get the posted data
$data = json_decode(file_get_contents("php://input"));

if ($data && isset($data->rollno, $data->email, $data->password)) {
    if (filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
        if (is_allowed_rollno($conn, $data->rollno)) {
            if (isAlreadyRegistered($conn, $data->rollno, $data->email)) {
                // Set error response code to 409 (Conflict) if user is already registered
                http_response_code(409);
                echo json_encode(['status' => 'error', 'message' => 'User already registered.']);
            } else {
                if (registerUser($conn, $data->rollno, $data->email, $data->password)) {
                    // Set success response code to 200 (OK)
                    http_response_code(200);
                    echo json_encode(['status' => 'success', 'message' => 'Sign Up Done successfully, Please check your Email in Inbox and Spam folder and verify Immediately ']);
                } else {
                    // Set error response code to 500 (Internal Server Error) if email sending fails
                    http_response_code(500);
                    echo json_encode(['status' => 'error', 'message' => 'Failed to send verification email.']);
                }
            }
        } else {
            // Set error response code to 403 (Forbidden) if roll number is not allowed
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'You are not allowed to Sign Up. Please contact Hostel Administration']);
        }
    } else {
        // Set error response code to 400 (Bad Request) if email is invalid
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
    }
} else {
    // Default response for invalid input
    echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
}

//$conn->close();
?>
