<?php
require_once("headers.php");
require_once("db.php");
//require_once("verify_student_cookie.php");

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
        //check_rollno($data->rollno);

        $rollno = $data->rollno;

        // Prepare and execute query for student_form table
        $stmt = $conn->prepare("SELECT * FROM student_form WHERE rollno = ?");
        if (!$stmt) {
            http_response_code(500); // Internal Server Error
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param("s", $rollno);
        $stmt->execute();
        $result_form = $stmt->get_result();

        if ($result_form->num_rows > 0) {
            $studentData = $result_form->fetch_assoc();
        } else {
            http_response_code(404); // Not Found
            throw new Exception('No details found in student_form table for the provided roll number.');
        }
        $stmt->close();

        // Prepare and execute query for student_docs table
        $stmt = $conn->prepare("SELECT * FROM student_docs WHERE rollno = ?");
        if (!$stmt) {
            http_response_code(500); // Internal Server Error
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param("s", $rollno);
        $stmt->execute();
        $result_docs = $stmt->get_result();

        if ($result_docs->num_rows > 0) {
            $documents = $result_docs->fetch_assoc();
        } else {
            http_response_code(404); // Not Found
            throw new Exception('No details found in student_docs table for the provided roll number.');
        }
        $stmt->close();

        // Return the combined result in JSON format
        http_response_code(200); // OK
        echo json_encode(['status' => 'success', 'message' => 'Self verification done.', 'documents' => $documents, 'studentData' => $studentData]);
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
