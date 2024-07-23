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

    if (isset($data->rollno, $data->hostel_name, $data->room_no)) {
        check_rollno($data->rollno);

        $rollno = $data->rollno;
        $hostel_name = $data->hostel_name;
        $room_no = $data->room_no;

        // Check if the room is already booked
        $stmt = $conn->prepare("SELECT requester_rollno, accepter_rollno FROM booking WHERE room_no = ? AND hostel_name = ?");
        if (!$stmt) {
            http_response_code(500); // Internal Server Error
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param("ss", $room_no, $hostel_name);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            http_response_code(200); // Conflict
            echo json_encode(['status' => 'error', 'message' => 'Room is already booked.', "isBooked" => true]);
            $stmt->close();
            $conn->close();
            exit;
        }
        $stmt->close();

        // Find the other roll number from roommate_requests
        $stmt = $conn->prepare("SELECT requester_rollno, accepter_rollno FROM roommate_requests WHERE (requester_rollno = ? OR accepter_rollno = ?) AND requester_flag = 1 AND accepter_flag = 1");
        if (!$stmt) {
            http_response_code(500); // Internal Server Error
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param("ss", $rollno, $rollno);
        $stmt->execute();
        $stmt->store_result();

        $requester_rollno = null;
        $accepter_rollno = null;
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($requester_rollno, $accepter_rollno);
            $stmt->fetch();
           
            if ($rollno == $requester_rollno) {
                $other_rollno = $accepter_rollno;
            } else {
                $other_rollno = $requester_rollno;
            }
        } else {
            http_response_code(404); // Not Found
            echo json_encode(['status' => 'error', 'message' => 'No matching roommate request found.']);
            $stmt->close();
            $conn->close();
            exit;
        }
        $stmt->close();

        // Verify clerk_verified flag for both students in student_form and student_doc
        $stmt = $conn->prepare("
            SELECT clerk_verified FROM student_form WHERE rollno = ?
            UNION ALL
            SELECT clerk_verified FROM student_docs WHERE rollno = ?
            UNION ALL
            SELECT clerk_verified FROM student_form WHERE rollno = ?
            UNION ALL
            SELECT clerk_verified FROM student_docs WHERE rollno = ?
        ");
        if (!$stmt) {
            http_response_code(500); // Internal Server Error
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param("ssss", $rollno, $rollno, $other_rollno, $other_rollno);
        $stmt->execute();
        $stmt->store_result();

        $clerk_verified = true;
        while ($stmt->fetch()) {
            $stmt->bind_result( $clerk_verified);
            if ( $clerk_verified!= 1) {
                $clerk_verified = false;
                break;
            }
        }
        $stmt->close();

        if (!$clerk_verified) {
            http_response_code(403); // Forbidden
            echo json_encode(['status' => 'error', 'message' => 'Clerk verification required for both students.']);
            $conn->close();
            exit;
        }

        // Check if there are vacant seats in the room
        $stmt = $conn->prepare("SELECT vacant_seats, filled_seats FROM hostel_rooms WHERE room_no = ? AND hostel_name = ?");
        if (!$stmt) {
            http_response_code(500); // Internal Server Error
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param("ss", $room_no, $hostel_name);
        $stmt->execute();
        $stmt->bind_result($vacant_seats, $filled_seats);
        $stmt->fetch();
        $stmt->close();

        if ($vacant_seats <= 0) {
            http_response_code(409); // Conflict
            echo json_encode(['status' => 'error', 'message' => 'No vacant seats available in the selected room.', 'isBooked' => true]);
            $conn->close();
            exit;
        }

        // Start transaction
        $conn->begin_transaction();

        // Store the booking details
        $stmt = $conn->prepare("INSERT INTO booking (requester_rollno, accepter_rollno, room_no, hostel_name, alloted) VALUES (?, ?, ?, ?, 0)");
        if (!$stmt) {
            $conn->rollback();
            http_response_code(500); // Internal Server Error
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param("ssss", $rollno, $other_rollno, $room_no, $hostel_name);

        if (!$stmt->execute()) {
            $conn->rollback();
            http_response_code(500); // Internal Server Error
            throw new Exception('Database error: ' . $stmt->error);
        }
        $stmt->close();

        // Update the hostel_rooms table to decrement vacant_seats and increment filled_seats
        $stmt = $conn->prepare("UPDATE hostel_rooms SET vacant_seats = vacant_seats - 2, filled_seats = filled_seats + 2 WHERE room_no = ? AND hostel_name = ?");
        if (!$stmt) {
            $conn->rollback();
            http_response_code(500); // Internal Server Error
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param("ss", $room_no, $hostel_name);

        if (!$stmt->execute()) {
            $conn->rollback();
            http_response_code(500); // Internal Server Error
            throw new Exception('Database error: ' . $stmt->error);
        }
        $stmt->close();

        // Commit transaction
        $conn->commit();

        http_response_code(200); // OK
        echo json_encode(['status' => 'success', 'message' => 'Booking request submitted successfully.']);
    } else {
        http_response_code(400); // Bad Request
        throw new Exception('Invalid input');
    }

    $conn->close();
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    error_log('Error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    // if (isset($stmt)) {
    //     $stmt->close();
    // }
    // $conn->close();
}
?>