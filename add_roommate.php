<?php
require_once("headers.php");
require_once("db.php");
require_once("verify_student_cookie.php");


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
        $mail->SMTPSecure = 'tls'; // Enable TLS encryption, ssl also accepted
        $mail->Port = 587; // TCP port to connect to
    
        $mail->setFrom('hostels@nitj.ac.in', 'NITJ || Hostels');
        $mail->addAddress($toEmail, $toName); // Add a recipient
        $mail->isHTML(true); // Set email format to HTML
    
        $mail->Subject = 'Roommate Request for NITJ Hostel Allotment';
        $mail->Body = "<p>You have received a roommate request from roll number $fromRollNo. Please log in to your account to accept or reject the request.</p>";
    
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

        // Check if either the requester or the accepter is already in a roommate request
        $stmt = $conn->prepare("SELECT requester_flag FROM roommate_requests WHERE (requester_rollno = ? OR accepter_rollno = ?) AND (requester_flag = 1 OR accepter_flag = 1)");
        if (!$stmt) {
            http_response_code(500); // Internal Server Error
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param("ss", $requestedRollno, $requestedRollno);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            http_response_code(409); // Conflict
            echo json_encode(['status' => 'error', 'message' => 'Either you or the requested roommate is already involved in a request.']);
            $stmt->close();
            $conn->close();
            exit;
        }
        $stmt->close();

        // Fetch student details to whom the request is being sent
        $stmt = $conn->prepare("SELECT rollno, email, gender FROM student_form WHERE rollno = ?");
        if (!$stmt) {
            http_response_code(500); // Internal Server Error
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param("s", $requestedRollno);
        $stmt->execute();
        $result = $stmt->get_result();

        // Fetch the requester's gender
        $gender_stmt = $conn->prepare("SELECT gender FROM student_form WHERE rollno = ?");
        if (!$gender_stmt) {
            http_response_code(500); // Internal Server Error
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $gender_stmt->bind_param("s", $requesterRollno);
        $gender_stmt->execute();
        $result_gender = $gender_stmt->get_result();

        if ($result_gender->num_rows > 0) {
            $fetched_gender = $result_gender->fetch_assoc();
            $requesterGender = $fetched_gender['gender'];
        }

        if ($result->num_rows > 0) {
            $studentDetails = $result->fetch_assoc();
            $accepterRollno = $studentDetails['rollno'];
            $accepterEmail = $studentDetails['email'];
            $accepterGender = $studentDetails['gender'];
            
            if ($accepterGender == $requesterGender) {
                // Send email notification
                $emailResult = sendEmailNotification($accepterEmail, $accepterRollno, $requesterRollno);

                if (!$emailResult['success']) {
                    // Log the failure, but continue
                    error_log("Roommate-request email failed: " . $emailResult['message']);
                    // Optionally include a warning in your JSON response:
                    $warning = ' (Warning: email failed to send)';
                } else {
                    $warning = '';
                }

                // Insert request into database
                $stmt = $conn->prepare(
                    "INSERT INTO roommate_requests 
                    (requester_rollno, requester_flag, accepter_rollno, accepter_flag) 
                    VALUES (?, 1, ?, 0)"
                );
                if (! $stmt) {
                    http_response_code(500);
                    throw new Exception("Prepare statement failed: " . $conn->error);
                }
                $stmt->bind_param("ss", $requesterRollno, $accepterRollno);

                if ($stmt->execute()) {

                    $newStep = '5.1';
                    if (! updateStudentStep($conn, $newStep)) {
                        throw new Exception(json_encode($_SESSION));
                    }
                    $_SESSION['step'] = $newStep;

                    http_response_code(200);
                    echo json_encode([
                        'status'  => 'success',
                        'message' => 'Roommate request sent successfully' . $warning
                    ]);
                } else {
                    http_response_code(500);
                    throw new Exception('Database error: ' . $stmt->error);
                }

                $stmt->close();
            } else {
                http_response_code(400); // Bad Request
                echo json_encode(['status' => 'error', 'message' => 'Please choose a roommate of the same gender.']);
                $conn->close();
                exit;
            }
        } else {
            http_response_code(404); // Not Found
            throw new Exception('Student not found or may not have filled the application form yet');
        }
    } else {
        http_response_code(400); // Bad Request
        throw new Exception('Invalid input');
    }

    $conn->close();
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>
