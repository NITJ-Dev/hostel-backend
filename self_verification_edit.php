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

    if (isset($data->rollno, $data->flag, $data->studentdata)) {
        check_rollno($data->rollno);

        $rollno = $data->rollno;
        $self_verified_flag = $data->flag;
        $student_data = $data->studentdata;

        // If no changes have been made, exit
        if ($self_verified_flag == 0) {
            http_response_code(200); // OK
            echo json_encode(['status' => 'no_change', 'message' => 'Response has been saved successfully']);
            exit;
        }

        $self_verified = 1;

        // Update the student_form table with the provided student data
        $stmt = $conn->prepare("UPDATE student_form SET full_name = ?, father_name = ?, mother_name = ?, year = ?, branch = ?, gender = ?, email = ?, self_mobile = ?, father_mobile = ?, mother_mobile = ?, sibling_mobile = ?, guardian_mobile = ?, postal_address = ?, state = ?, local_guardian_address = ?, physically_handicapped = ?, blood_group = ?, self_verified = ? WHERE rollno = ?");
        if (!$stmt) {
            http_response_code(500); // Internal Server Error
            throw new Exception("Prepare statement failed: " . $conn->error);
        }

        $stmt->bind_param(
            "sssssssssssssssssss",
            $student_data->full_name,
            $student_data->father_name,
            $student_data->mother_name,
            $student_data->year,
            $student_data->branch,
            $student_data->gender,
            $student_data->email,
            $student_data->self_mobile,
            $student_data->father_mobile,
            $student_data->mother_mobile,
            $student_data->sibling_mobile,
            $student_data->guardian_mobile,
            $student_data->postal_address,
            $student_data->state,
            $student_data->local_guardian_address,
            $student_data->physically_handicapped,
            $student_data->blood_group,
            $self_verified,
            $rollno
        );

        if ($stmt->execute()) {
            http_response_code(200); // OK
            echo json_encode(['status' => 'success', 'message' => 'Student details updated successfully']);
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
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    $conn->close();
}
?>
