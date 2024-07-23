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

    if (!isset($data->rollno)) {
        http_response_code(400); // Bad Request
        throw new Exception('Invalid input');
    }
    // check_rollno($data->rollno);

    check_rollno($data->rollno);

     // Check if rollno is already in booking table
    $stmt = $conn->prepare("SELECT requester_rollno, accepter_rollno FROM booking WHERE requester_rollno = ? OR accepter_rollno = ?");
    if (!$stmt) {
        http_response_code(500); // Internal Server Error
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    $stmt->bind_param("ss", $data->rollno, $data->rollno);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($requester_rollno, $accepter_rollno);
        $stmt->fetch();

        // Check if requester_rollno is the same as accepter_rollno
        $flag = ($requester_rollno == $accepter_rollno);
        if($flag==true){
            http_response_code(200); // OK
            echo json_encode(['status' => 'error', 'message' => 'Room is already booked for this roll number.', 'isBooked' => true]);
            $stmt->close();
            $conn->close();
            exit;
        }
    }

    $userRoll = $data->rollno;

    // Function to execute prepared statements and handle errors
    function executePreparedStmt($conn, $query, $params) {
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param(...$params);
        $stmt->execute();
        return $stmt;
    }

    // Fetch revoke details
    $stmt = executePreparedStmt($conn, "SELECT revoke_requester, requester_flag, revoke_accepter, accepter_flag FROM revoke_requests WHERE revoke_requester = ? OR revoke_accepter = ?", ["ss", $userRoll, $userRoll]);
    $revokeDetails = [];
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($revoke_requester, $requester_flag, $revoke_accepter, $accepter_flag);
        $stmt->fetch();
        $revokeDetails = [
            'revoke_requester' => $revoke_requester,
            'requester_flag' => $requester_flag,
            'revoke_accepter' => $revoke_accepter,
            'accepter_flag' => $accepter_flag
        ];
    }
    $stmt->close();

    // Fetch roommate request details
    $stmt = executePreparedStmt($conn, "SELECT requester_rollno, requester_flag, accepter_rollno, accepter_flag FROM roommate_requests WHERE requester_rollno = ? OR accepter_rollno = ?", ["ss", $userRoll, $userRoll]);
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($requester_rollno, $requester_flag, $accepter_rollno, $accepter_flag);
        $stmt->fetch();
        $stmt->close();

        // Fetch self-verification status
        $stmt = executePreparedStmt($conn, "SELECT rollno, email, self_verified FROM student_form WHERE rollno = ?", ["s", $userRoll]);
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $self = $result->fetch_assoc();
            $self_verification = $self['self_verified'];
        } else {
            http_response_code(404); // Not Found
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit;
        }
        $stmt->close();

        $roommate = null;
        $request_status = "Pending";

        if ($userRoll == $requester_rollno) {
            // Fetch accepter details
            $stmt = executePreparedStmt($conn, "SELECT rollno, email FROM student_form WHERE rollno = ?", ["s", $accepter_rollno]);
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $roommate = $result->fetch_assoc();
                $request_status = ($accepter_flag == 0) ? "Pending" : "Accepted";
            } else {
                http_response_code(404); // Not Found
                echo json_encode(['status' => 'error', 'message' => 'Accepter not found']);
                exit;
            }
            $stmt->close();
        } else {
            // Fetch requester details
            $stmt = executePreparedStmt($conn, "SELECT rollno, email FROM student_form WHERE rollno = ?", ["s", $requester_rollno]);
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $roommate = $result->fetch_assoc();
                $request_status = ($accepter_flag == 1) ? "Accepted" : "Pending";
            } else {
                http_response_code(404); // Not Found
                echo json_encode(['status' => 'error', 'message' => 'Requester not found']);
                exit;
            }
            $stmt->close();
        }

        http_response_code(200); // OK
        echo json_encode(['status' => 'success', 'message' => 'Fetched successfully.', 'request_status' => $request_status, 'requester' => $requester_rollno, 'roommate' => $roommate, 'revoke_details' => $revokeDetails, 'self_verification' => $self_verification]);

    } else {
        http_response_code(200); // OK
        echo json_encode(['status' => 'success', 'flag' => '1', 'message' => 'No roommate request found']);
    }

    $conn->close();
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    $conn->close();
}
?>