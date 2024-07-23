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

    $rollno = $data->rollno;

    // Start transaction
    $conn->begin_transaction();

    // Prepare the update statement for student_form
    $query1 = "UPDATE student_form SET clerk_verified = 1 WHERE rollno = ?";
    $stmt1 = $conn->prepare($query1);
    if (!$stmt1) {
        throw new Exception('Error preparing statement for student_form: ' . $conn->error);
    }

    // Bind the roll number parameter
    $stmt1->bind_param('s', $rollno);

    // Execute the statement
    if (!$stmt1->execute()) {
        throw new Exception('Error executing statement for student_form: ' . $stmt1->error);
    }

    // Check if any rows were updated in student_form
    if ($stmt1->affected_rows === 0) {
        http_response_code(404); // Not Found
        throw new Exception('No record found with the provided roll number in student_form');
    }

    // Prepare the update statement for student_docs
    $query2 = "UPDATE student_docs SET clerk_verified = 1 WHERE rollno = ?";
    $stmt2 = $conn->prepare($query2);
    if (!$stmt2) {
        throw new Exception('Error preparing statement for student_docs: ' . $conn->error);
    }

    // Bind the roll number parameter
    $stmt2->bind_param('s', $rollno);

    // Execute the statement
    if (!$stmt2->execute()) {
        throw new Exception('Error executing statement for student_docs: ' . $stmt2->error);
    }

    // Check if any rows were updated in student_docs
    if ($stmt2->affected_rows === 0) {
        http_response_code(404); // Not Found
        throw new Exception('No record found with the provided roll number in student_docs');
    }

    // Commit the transaction
    $conn->commit();

    // Return success response
    http_response_code(200); // OK
    echo json_encode(['status' => 'success', 'message' => 'Records updated successfully']);

    // Close the statements and connection
    $stmt1->close();
    $stmt2->close();
    $conn->close();
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    // if ($conn->in_transaction) {
    //     $conn->rollback();
    // }
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    if (isset($stmt1) && $stmt1 instanceof mysqli_stmt) {
        $stmt1->close();
    }
    if (isset($stmt2) && $stmt2 instanceof mysqli_stmt) {
        $stmt2->close();
    }
    $conn->close();
}
?>
