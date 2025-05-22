<?php
require_once("headers.php");
require_once("db.php");
require_once("verify_student_cookie.php");
require_once("update_step.php");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Enable logging
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php_errors.log'); // Ensure this path is writable by the web server

require 'vendor/autoload.php'; // Load PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to send email notification
function sendEmailNotification($toEmail, $toName, $fromRollNo)
{
    try {
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'hostels@nitj.ac.in';
        $mail->Password = 'juszznxasmrurfsl';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('hostels@nitj.ac.in', 'NITJ || Hostels');
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);

        $mail->Subject = 'Roommate Revoke Request for NITJ Hostel Allotment';
        $mail->Body = "<p>You have received a roommate revoke request from roll number $fromRollNo. Please log in to your account to accept or reject the revoke request.</p>";

        if (!$mail->send()) {
            return ["success" => false, "message" => "Mailer Error: " . $mail->ErrorInfo];
        }
        return ["success" => true, "message" => "Message has been sent"];
    } catch (Exception $e) {
        error_log('Mailer Exception: ' . $e->getMessage());
        return ["success" => false, "message" => "Mailer Exception: " . $e->getMessage()];
    }
}

try {
    $data = json_decode(file_get_contents("php://input"));
    if ($data === null) {
        http_response_code(400);
        throw new Exception('Invalid JSON input');
    }

    if (!isset($data->rollno, $data->requested_rollno)) {
        http_response_code(400);
        throw new Exception('Invalid input');
    }

    check_rollno($data->rollno);
    $requesterRollno = $data->rollno;
    $requestedRollno = $data->requested_rollno;

    // Fetch existing request (and accepter email)
    $stmt = $conn->prepare(
        "SELECT
            requester_flag,
            accepter_flag,
            (SELECT email FROM student_form WHERE rollno = ?) AS accepter_email
         FROM roommate_requests
         WHERE (requester_rollno = ? AND accepter_rollno = ?)
            OR (requester_rollno = ? AND accepter_rollno = ?)"
    );
    if (!$stmt) {
        http_response_code(500);
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param(
        "sssss",
        $requestedRollno,
        $requesterRollno,
        $requestedRollno,
        $requestedRollno,
        $requesterRollno
    );
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        throw new Exception('No roommate request found between the given roll numbers.');
    }

    $row = $result->fetch_assoc();
    $requesterFlag = $row['requester_flag'];
    $accepterFlag = $row['accepter_flag'];
    $accepterEmail = $row['accepter_email'];
    $stmt->close();

    // Case 1: revoke before acceptance
    if ($requesterFlag == 1 && $accepterFlag == 0) {
        // Move to logs
        $stmt = $conn->prepare(
            "INSERT INTO revoke_requests_logs
               (revoke_requester, requester_flag, revoke_accepter, accepter_flag)
             SELECT requester_rollno, requester_flag, accepter_rollno, accepter_flag
             FROM roommate_requests
             WHERE requester_rollno = ? AND accepter_rollno = ?"
        );
        if (!$stmt) {
            http_response_code(500);
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ss", $requesterRollno, $requestedRollno);
        $stmt->execute();
        $stmt->close();

        // Delete original request
        $stmt = $conn->prepare(
            "DELETE FROM roommate_requests
             WHERE requester_rollno = ? AND accepter_rollno = ?"
        );
        if (!$stmt) {
            http_response_code(500);
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ss", $requesterRollno, $requestedRollno);
        if (!$stmt->execute()) {
            http_response_code(500);
            throw new Exception('Database error: ' . $stmt->error);
        }
        $stmt->close();

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Roommate request revoked successfully and moved to logs.'
        ]);
        $conn->close();
        exit;
    }

    // Case 2: initiate revoke after acceptance
    if ($requesterFlag == 1 && $accepterFlag == 1) {
        // Check if room already booked by this pair
        $stmt = $conn->prepare(
            "SELECT COUNT(*) FROM booking
             WHERE requester_rollno = ? OR accepter_rollno = ?"
        );
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ss", $requesterRollno, $requesterRollno);
        $stmt->execute();
        $stmt->bind_result($rowCount);
        $stmt->fetch();
        $stmt->close();

        if ($rowCount > 0) {
            http_response_code(402);
            echo json_encode([
                'status' => 'failure',
                'message' => 'Room is already booked by roommate; cannot revoke now.'
            ]);
            $conn->close();
            exit;
        }

        // Ensure no existing revoke request
        $stmt = $conn->prepare(
            "SELECT 1 FROM revoke_requests
             WHERE revoke_requester = ? AND revoke_accepter = ?"
        );
        if (!$stmt) {
            http_response_code(500);
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ss", $requesterRollno, $requestedRollno);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            http_response_code(400);
            throw new Exception('A revoke request already exists for these roll numbers.');
        }
        $stmt->close();

        // Insert new revoke request
        $stmt = $conn->prepare(
            "INSERT INTO revoke_requests
               (revoke_requester, requester_flag, revoke_accepter, accepter_flag, revoker_timestamp)
             SELECT ?, 1, ?, 0, NOW()
             FROM roommate_requests
             WHERE (requester_rollno = ? AND accepter_rollno = ?)
                OR (requester_rollno = ? AND accepter_rollno = ?)"
        );
        if (!$stmt) {
            http_response_code(500);
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param(
            "ssssss",
            $requesterRollno,
            $requestedRollno,
            $requesterRollno,
            $requestedRollno,
            $requestedRollno,
            $requesterRollno
        );
        if (!$stmt->execute()) {
            http_response_code(500);
            throw new Exception('Database error: ' . $stmt->error);
        }
        $stmt->close();

        // Send email but do NOT abort on failure
        $emailResult = sendEmailNotification($accepterEmail, $requestedRollno, $requesterRollno);
        if (!$emailResult['success']) {
            error_log("Revoke-request email failed: " . $emailResult['message']);
            $warning = ' (Warning: email failed to send)';
        } else {
            $warning = '';
        }

        $newStep = '5.1';
        if (!updateStudentStep($conn, $newStep)) {
            throw new Exception(json_encode($_SESSION));
        }
        $_SESSION['step'] = $newStep;

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Revoke request initiated successfully. Awaiting acceptance from the other party.' . $warning
        ]);
        $conn->close();
        exit;
    }

    // Any other case is invalid
    http_response_code(400);
    throw new Exception('Cannot initiate revoke on a request that is not accepted or already revoked.');

} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    if ($conn) {
        $conn->close();
    }
}
