<?php
require_once("headers.php");
require_once("db.php");
//require_once("verify_student_cookie.php");

try {
    $data = json_decode(file_get_contents("php://input"));

    if ($data === null) {
        http_response_code(400); // Bad Request
        throw new Exception('Invalid JSON input');
    }

    if (isset($data->rollno)) {
        //check_rollno($data->rollno);
        
        $accepterRollno = $data->rollno;

        // Fetch the request details before deleting
        $stmt = $conn->prepare("SELECT * FROM roommate_requests WHERE accepter_rollno = ?");
        if (!$stmt) {
            http_response_code(500); // Internal Server Error
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param("s", $accepterRollno);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $requestDetails = $result->fetch_assoc();

            // Insert the request details into roommate_requests_logs
            $stmt = $conn->prepare("INSERT INTO roommate_requests_logs (requester_rollno, requester_flag, accepter_rollno, accepter_flag) VALUES (?, ?, ?, -1)");
            if (!$stmt) {
                http_response_code(500); // Internal Server Error
                throw new Exception("Prepare statement failed: " . $conn->error);
            }
            $stmt->bind_param("sis", $requestDetails['requester_rollno'], $requestDetails['requester_flag'], $requestDetails['accepter_rollno']);

            if ($stmt->execute()) {
                // Delete the request from roommate_requests
                $stmt = $conn->prepare("DELETE FROM roommate_requests WHERE accepter_rollno = ?");
                if (!$stmt) {
                    http_response_code(500); // Internal Server Error
                    throw new Exception("Prepare statement failed: " . $conn->error);
                }
                $stmt->bind_param("s", $accepterRollno);
                if ($stmt->execute()) {
                    http_response_code(200); // OK
                    echo json_encode(['status' => 'success', 'message' => 'Request rejected successfully.']);
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
            throw new Exception('No request found for the given roll number.');
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
