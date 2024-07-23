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

    if (isset($data->rollno)) {
        check_rollno($data->rollno);
        
        $rollno = $data->rollno;

        // Fetch booking details from booking table
        $stmt = $conn->prepare("SELECT hostel_name, room_no, requester_rollno, accepter_rollno FROM booking WHERE requester_rollno = ? OR accepter_rollno = ?");
        if (!$stmt) {
            http_response_code(500); // Internal Server Error
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param("ss", $rollno, $rollno);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $booking = $result->fetch_assoc();
            $hostel_name = $booking['hostel_name'];
            $room_no = $booking['room_no'];
            $requester_rollno = $booking['requester_rollno'];
            $accepter_rollno = $booking['accepter_rollno'];

            // Fetch roommate details from student_form table
            if ($requester_rollno == $rollno) {
                $roommate_rollno = $accepter_rollno;
            } else {
                $roommate_rollno = $requester_rollno;
            }

            $stmt = $conn->prepare("SELECT rollno, full_name FROM student_form WHERE rollno = ?");
            if (!$stmt) {
                http_response_code(500); // Internal Server Error
                throw new Exception("Prepare statement failed: " . $conn->error);
            }
            $stmt->bind_param("s", $roommate_rollno);
            $stmt->execute();
            $roommate_result = $stmt->get_result();

            if ($roommate_result->num_rows > 0) {
                $roommate = $roommate_result->fetch_assoc();
                $roommate_details = [
                    'roll_no' => $roommate['rollno'],
                    'Roommate_name' => $roommate['full_name']
                ];
            } else {
                $roommate_details = null;
            }

            // Prepare response data
            $response = [
                'status' => 'success',
                'message' => 'Fetched your confirmation',
                'data' => [
                    'hostel_name' => $hostel_name,
                    'room_no' => $room_no,
                    'roommate' => $roommate_details
                ]
            ];

            http_response_code(200); // OK
            echo json_encode($response);
        } else {
            http_response_code(404); // Not Found
            echo json_encode(['status' => 'error', 'message' => 'Booking not found for the given roll number.']);
        }
    } else {
        http_response_code(400); // Bad Request
        throw new Exception('Invalid input');
    }

    $conn->close();
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    error_log('Error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>
