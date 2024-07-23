<?php
require_once("headers.php");
require_once("db.php");
// require_once("verify_student_cookie.php");

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
function sendEmailNotification($toEmail, $toName, $fromRollNo) {
    try {
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Set the SMTP server to send through
        $mail->SMTPAuth = true;
        $mail->Username = 'hostels@nitj.ac.in'; // SMTP username
        $mail->Password = 'juszznxasmrurfsl'; // SMTP password
        // $mail->Username = 'hostels.nitjalandhar@gmail.com'; // SMTP username
        // $mail->Password = 'ddihzzgmnumhsxhz'; // SMTP password
        $mail->SMTPSecure = 'tls'; // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 587; // TCP port to connect to

        $mail->setFrom('hostels@nitj.ac.in', 'NITJ || Hostels');
        $mail->addAddress($toEmail, $toName); // Add a recipient
        $mail->isHTML(true); // Set email format to HTML

        $mail->Subject = 'Roommate Revoke Request for NITJ Hostel Allotment';
        $mail->Body = "<p>You have received a roommate revoke request from roll number $fromRollNo. Please log in to your account to accept or reject the revoke request.</p>";

        if (!$mail->send()) {
            return ["success" => false, "message" => "Mailer Error: " . $mail->ErrorInfo];
        } else {
            return ["success" => true, "message" => "Message has been sent"];
        }
    } catch (Exception $e) {
        error_log('Mailer Error: ' . $e->getMessage());
        return ["success" => false, "message" => "Mailer Exception: " . $e->getMessage()];
    }
}

try {
    // Get the posted data
    $data = json_decode(file_get_contents("php://input"));

    if ($data === null) {
        http_response_code(400); // Bad Request
        throw new Exception('Invalid JSON input');
    }

    if (isset($data->rollno, $data->requested_rollno)) {
        check_rollno($data->rollno);

        $requesterRollno = $data->rollno;
        $requestedRollno = $data->requested_rollno;

        // Check if there is an existing request between the two students
        $stmt = $conn->prepare("SELECT requester_flag, accepter_flag, (SELECT email FROM student_form WHERE rollno = ?) AS accepter_email FROM roommate_requests WHERE (requester_rollno = ? AND accepter_rollno = ?) OR (requester_rollno = ? AND accepter_rollno = ?)");
        if (!$stmt) {
            http_response_code(500); // Internal Server Error
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param("sssss", $requestedRollno, $requesterRollno, $requestedRollno, $requestedRollno, $requesterRollno);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $requestDetails = $result->fetch_assoc();
            $requesterFlag = $requestDetails['requester_flag'];
            $accepterFlag = $requestDetails['accepter_flag'];
            $accepterEmail = $requestDetails['accepter_email'];

            // Case 1: Revoke before acceptance
            if ($requesterFlag == 1 && $accepterFlag == 0) {
                $stmt = $conn->prepare("INSERT INTO revoke_requests_logs (revoke_requester, requester_flag, revoke_accepter, accepter_flag) SELECT requester_rollno, requester_flag, accepter_rollno, accepter_flag FROM roommate_requests WHERE requester_rollno = ? AND accepter_rollno = ?");
                if (!$stmt) {
                    http_response_code(500); // Internal Server Error
                    throw new Exception("Prepare statement failed: " . $conn->error);
                }
                $stmt->bind_param("ss", $requesterRollno, $requestedRollno);

                if ($stmt->execute()) {
                    $stmt = $conn->prepare("DELETE FROM roommate_requests WHERE requester_rollno = ? AND accepter_rollno = ?");
                    if (!$stmt) {
                        http_response_code(500); // Internal Server Error
                        throw new Exception("Prepare statement failed: " . $conn->error);
                    }
                    $stmt->bind_param("ss", $requesterRollno, $requestedRollno);

                    if ($stmt->execute()) {
                        http_response_code(200); // OK
                        echo json_encode(['status' => 'success', 'message' => 'Roommate request revoked successfully and moved to logs.']);
                    } else {
                        http_response_code(500); // Internal Server Error
                        throw new Exception('Database error: ' . $stmt->error);
                    }
                } else {
                    http_response_code(500); // Internal Server Error
                    throw new Exception('Database error: ' . $stmt->error);
                }
            } 
            // Case 2: Initiate revoke after acceptance
            else if ($requesterFlag == 1 && $accepterFlag == 1) {


               // Check if the users roommate is self-verified
                $stmt = $conn->prepare("SELECT self_verified FROM student_form WHERE rollno = ?");
                if (!$stmt) {
                    http_response_code(500); // Internal Server Error
                    throw new Exception("Prepare statement failed: " . $conn->error);
                }
                $stmt->bind_param("s", $requestedRollno);
                $stmt->execute();
                $stmt->bind_result($self_verified);
                $stmt->fetch();

                if ($self_verified==1) {
                    http_response_code(402); // Forbidden
                    echo json_encode(['status' => 'success', 'message' => 'Your roommate has proceeded further, and therefore you cannot revoke your request at this time.','isRoommateProceed' => true]);
                    exit;
                }

                $stmt->close();





                // Check if a revoke request already exists
                $stmt = $conn->prepare("SELECT * FROM revoke_requests WHERE revoke_requester = ? AND revoke_accepter = ?");
                if (!$stmt) {
                    http_response_code(500); // Internal Server Error
                    throw new Exception("Prepare statement failed: " . $conn->error);
                }
                $stmt->bind_param("ss", $requesterRollno, $requestedRollno);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    http_response_code(400); // Bad Request
                    throw new Exception('A revoke request already exists for these roll numbers.');
                }
                $stmt->close();

                $revokerRollno = $requesterRollno;

                $stmt = $conn->prepare("INSERT INTO revoke_requests (revoke_requester, requester_flag, revoke_accepter, accepter_flag, revoker_timestamp) SELECT ?, 1, ?, 0, NOW() FROM roommate_requests WHERE (requester_rollno = ? AND accepter_rollno = ?) OR (requester_rollno = ? AND accepter_rollno = ?)");
                if (!$stmt) {
                    http_response_code(500); // Internal Server Error
                    throw new Exception("Prepare statement failed: " . $conn->error);
                }
                $stmt->bind_param("ssssss", $requesterRollno, $requestedRollno, $requesterRollno, $requestedRollno, $requestedRollno, $requesterRollno);

                if ($stmt->execute()) {
                    // Send email notification to the accepter
                    $emailResult = sendEmailNotification($accepterEmail, $requestedRollno, $requesterRollno);

                    if (!$emailResult['success']) {
                        http_response_code(500); // Internal Server Error
                        echo json_encode(['status' => 'error', 'message' => $emailResult['message']]);
                        $stmt->close();
                        $conn->close();
                        exit;
                    }

                    http_response_code(200); // OK
                    echo json_encode(['status' => 'success', 'message' => 'Revoke request initiated successfully. Awaiting acceptance from the other party.']);
                } else {
                    http_response_code(500); // Internal Server Error
                    throw new Exception('Database error: ' . $stmt->error);
                }
            } else {
                http_response_code(400); // Bad Request
                throw new Exception('Cannot initiate revoke on a request that is not accepted or already revoked.');
            }
        } else {
            http_response_code(404); // Not Found
            throw new Exception('No roommate request found between the given roll numbers.');
        }

        $stmt->close();
    } else {
        http_response_code(400); // Bad Request
        throw new Exception('Invalid input');
    }

    $conn->close();
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>
