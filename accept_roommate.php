<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php_errors.log'); // Ensure this path is writable by the web server

require_once("headers.php");
require_once("db.php");
require_once("verify_student_cookie.php");

try {
    $data = json_decode(file_get_contents("php://input"));

    if ($data === null) {
        http_response_code(400); // Bad Request
        throw new Exception('Invalid JSON input');
    }

    if (isset($data->rollno)) {
        check_rollno($data->rollno);
        $accepterRollno = $data->rollno;

        $stmt = $conn->prepare("UPDATE roommate_requests SET accepter_flag = 1 WHERE accepter_rollno = ?");
        if (!$stmt) {
            http_response_code(500); // Internal Server Error
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param("s", $accepterRollno);
        
        if ($stmt->execute()) {
            http_response_code(200); // OK
            echo json_encode(['status' => 'success', 'message' => 'Request accepted successfully.']);
        } else {
            http_response_code(500); // Internal Server Error
            throw new Exception('Database error: ' . $stmt->error);
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
