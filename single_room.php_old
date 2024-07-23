<?php
require_once("headers.php");
require_once("db.php");
//require_once("verify_student_cookie.php");

// Get the posted data
$data = json_decode(file_get_contents("php://input"));

if ($data && isset($data->rollno)) {
    //check_rollno($data->rollno);

    $rollno = $data->rollno;

    // // Check if roll number belongs to allowed_rollno with course_sem 'btech7'
    // $stmt_course_sem = $conn->prepare("SELECT course_sem FROM allowed_rollno WHERE rollno = ? AND course_sem = 'btech7'");
    // $stmt_course_sem->bind_param("s", $rollno);
    // $stmt_course_sem->execute();
    // $result_course_sem = $stmt_course_sem->get_result();

    // if ($result_course_sem->num_rows > 0) {
        // Check self_verified in student_docs
        $stmt_self_verified = $conn->prepare("SELECT self_verified FROM student_form WHERE rollno = ?");
        $stmt_self_verified->bind_param("s", $rollno);
        $stmt_self_verified->execute();
        $result_self_verified = $stmt_self_verified->get_result();

        if ($result_self_verified->num_rows > 0) {
            $self_verified_data = $result_self_verified->fetch_assoc();
            $self_verified = $self_verified_data['self_verified'];

            // if ($self_verified == 1) {
                // Check if the user has been allotted a room
                $stmt_booking = $conn->prepare("SELECT * FROM booking WHERE requester_rollno = ? OR accepter_rollno = ?");
                $stmt_booking->bind_param("ss", $rollno, $rollno);
                $stmt_booking->execute();
                $result_booking = $stmt_booking->get_result();

                if ($result_booking->num_rows > 0) {
                    $booking_data = $result_booking->fetch_assoc();
                    $room_allotted = true;
                    $hostel_name = $booking_data['hostel_name'];
                    $room_no = $booking_data['room_no'];
                } else {
                    $room_allotted = false;
                    $hostel_name = null;
                    $room_no = null;
                }

                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Validation successful',
                    'self_verified' => $self_verified,
                    'room_allotted' => $room_allotted,
                    'hostel_name' => $hostel_name,
                    'room_no' => $room_no
                ]);
            // } else {
            //     http_response_code(403);
            //     echo json_encode(['status' => 'error', 'message' => 'Self verification pending']);
            // }
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Roll number not found in student_docs']);
        }

        $stmt_self_verified->close();
        $stmt_booking->close();
    // } else {
    //     http_response_code(403);
    //     echo json_encode(['status' => 'error', 'message' => 'Your roll number or course_sem is not allowed.']);
    // }

    // $stmt_course_sem->close();
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
}

$conn->close();
?>
