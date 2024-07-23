<?php
/*
require_once("headers.php");
require_once("db.php");

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
        $rollno = $data->rollno;

        // Fetch sem and course from student_form table
        $stmt = $conn->prepare("SELECT sem, course FROM student_form WHERE rollno = ?");
        if (!$stmt) {
            http_response_code(500); // Internal Server Error
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param("s", $rollno);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $studentDetails = $result->fetch_assoc();
            $sem = $studentDetails['sem'];
            $course = $studentDetails['course'];
            $concatenated = strtolower($course . $sem);

            // Construct the query to find allowed rooms based on concatenated variable
            $allowedRoomsQuery = "SELECT hostel_name,room_no, total_seats,filled_seats,vacant_seats, $concatenated as available,block FROM hostel_rooms WHERE $concatenated = 1";
            $allowedRoomsResult = $conn->query($allowedRoomsQuery);

            if ($allowedRoomsResult->num_rows > 0) {
                $rooms = [];
                while ($row = $allowedRoomsResult->fetch_assoc()) {
                    $rooms[] = $row;
                }
                http_response_code(200); // OK
                echo json_encode(['status' => 'success','message' => 'Rooms fetched', 'rooms' => $rooms, 'sem'=>$sem, 'course'=>$course]);
            } else {
                http_response_code(404); // Not Found
                echo json_encode(['status' => 'error', 'message' => 'No allowed rooms found for the given course and sem.']);
            }
        } else {
            http_response_code(404); // Not Found
            throw new Exception('Student not found in student_form table');
        }
    } else {
        http_response_code(400); // Bad Request
        throw new Exception('Invalid input');
    }

    $conn->close();
} catch (Exception $e) {
    http_response_code(500); //server error
    error_log('Error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
    */
?>


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
    
            // Check if rollno is already in booking table
            $stmt = $conn->prepare("SELECT requester_rollno, accepter_rollno FROM booking WHERE requester_rollno = ? OR accepter_rollno = ?");
            if (!$stmt) {
                http_response_code(500); // Internal Server Error
                throw new Exception("Prepare statement failed: " . $conn->error);
            }
            $stmt->bind_param("ss", $rollno, $rollno);
            $stmt->execute();
            $stmt->store_result();
    
            if ($stmt->num_rows > 0) {
                http_response_code(200); // Conflict
                echo json_encode(['status' => 'error', 'message' => 'Room is already booked for this roll number.' ,"isBooked"=>true]);
                $stmt->close();
                $conn->close();
                exit;
            }
            $stmt->close();
    
            // Fetch sem and course from student_form table
            $stmt = $conn->prepare("SELECT sem, course, gender FROM student_form WHERE rollno = ?");
            if (!$stmt) {
                http_response_code(500); // Internal Server Error
                throw new Exception("Prepare statement failed: " . $conn->error);
            }
            $stmt->bind_param("s", $rollno);
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($result->num_rows > 0) {
                $studentDetails = $result->fetch_assoc();
                $sem = $studentDetails['sem'];
                $course = $studentDetails['course'];
                $concatenated = strtolower($course . $sem);
                $gender=$studentDetails['gender'];
    
                // Construct the query to find allowed rooms based on concatenated variable
                $allowedRoomsQuery = "SELECT hostel_name, room_no, total_seats, filled_seats, vacant_seats, $concatenated as available, block FROM hostel_rooms WHERE $concatenated = 1";
                $allowedRoomsResult = $conn->query($allowedRoomsQuery);
    
                if ($allowedRoomsResult->num_rows > 0) {
                    $rooms = [];
                    while ($row = $allowedRoomsResult->fetch_assoc()) {
                        $rooms[] = $row;
                    }
                    http_response_code(200); // OK
                    echo json_encode(['status' => 'success','message' => 'Rooms fetched', 'rooms' => $rooms, 'sem' => $sem, 'course' => $course, 'gender' => $gender]);
                } else {
                    http_response_code(404); // Not Found
                    echo json_encode(['status' => 'error', 'message' => 'No allowed rooms found for the given course and sem.']);
                }
            } else {
                http_response_code(404); // Not Found
                throw new Exception('Student not found in student_form table');
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
    

