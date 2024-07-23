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

try {
    // Get the posted data
    $data = json_decode(file_get_contents("php://input"));

    if ($data === null) {
        http_response_code(400); // Bad Request
        throw new Exception('Invalid JSON input');
    }

    if (isset($data->rollno, $data->requested_rollno)) {
        check_rollno($data->requested_rollno);
        $requesterRollno = $data->rollno;
        $requestedRollno = $data->requested_rollno;

        // Check if there is an existing revoke request between the two students
        $stmt = $conn->prepare("SELECT * FROM revoke_requests WHERE revoke_requester = ? AND revoke_accepter = ?");
        if (!$stmt) {
            http_response_code(500); // Internal Server Error
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param("ss", $requesterRollno, $requestedRollno);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Move to revoke_request_logs
            $stmt = $conn->prepare("INSERT INTO revoke_requests_logs (revoke_requester, requester_flag, revoke_accepter, accepter_flag, revoker_timestamp) SELECT revoke_requester, requester_flag, revoke_accepter, accepter_flag, revoker_timestamp FROM revoke_requests WHERE revoke_requester = ? AND revoke_accepter = ?");
            if (!$stmt) {
                http_response_code(500); // Internal Server Error
                throw new Exception("Prepare statement failed: " . $conn->error);
            }
            $stmt->bind_param("ss", $requesterRollno, $requestedRollno);

            if ($stmt->execute()) {
                // Delete from revoke_requests
                $stmt = $conn->prepare("DELETE FROM revoke_requests WHERE revoke_requester = ? AND revoke_accepter = ?");
                if (!$stmt) {
                    http_response_code(500); // Internal Server Error
                    throw new Exception("Prepare statement failed: " . $conn->error);
                }
                $stmt->bind_param("ss", $requesterRollno, $requestedRollno);

                if ($stmt->execute()) {
                    // Update roommate_requests to reflect the revocation
                    $stmt = $conn->prepare("DELETE FROM roommate_requests WHERE (requester_rollno = ? AND accepter_rollno = ?) OR (requester_rollno = ? AND accepter_rollno = ?)");
                    if (!$stmt) {
                        http_response_code(500); // Internal Server Error
                        throw new Exception("Prepare statement failed: " . $conn->error);
                    }
                    $stmt->bind_param("ssss", $requesterRollno, $requestedRollno, $requestedRollno, $requesterRollno);

                    if ($stmt->execute()) {
                        http_response_code(200); // OK
                        echo json_encode(['status' => 'success', 'message' => 'Roommate request revoked successfully.']);
                    } else {
                        http_response_code(500); // Internal Server Error
                        throw new Exception('Database error: ' . $stmt->error);
                    }
                } else {
                    http_response_code(500); // Internal Server Error
                    throw new Exception('Database error: ' . $stmt->error);
                }
            } else {
                http_response_code(500); // Internal Server Error
                throw new Exception('Database error: ' . $stmt->error);
            }
        } else {
            http_response_code(404); // Not Found
            throw new Exception('No revoke request found between the given roll numbers.');
        }

        $stmt->close();
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
