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

    if (!isset($data->rollno, $data->subject, $data->message)) {
        http_response_code(400); // Bad Request
        throw new Exception('Invalid input');
    }

    $rollno = $data->rollno;
    $subject = $data->subject;
    $message = $data->message;

    // Prepare the update statement for student_form
    $query1 = "UPDATE student_form SET clerk_verified = 0, self_verified = 0, clerk_remarks = ? WHERE rollno = ?";
    $stmt = $conn->prepare($query1);
    if (!$stmt) {
        throw new Exception('Error preparing statement for student_form: ' . $conn->error);
    }

    $message_from_clerk = $subject." : ".$message;
    // Bind the parameters
    $stmt->bind_param('ss',$message_from_clerk , $rollno);

    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception('Error executing statement: ' . $stmt->error);
    }

    // Check if any rows were updated
    if ($stmt->affected_rows === 0) {
        http_response_code(404); // Not Found
        throw new Exception('No record found with the provided roll number or no change required');
    }

    // Prepare the update statement for student_docs
    $query2 = "UPDATE student_docs SET clerk_verified = 0 WHERE rollno = ? AND clerk_verified <> 0";
    $stmt = $conn->prepare($query2);
    if (!$stmt) {
        throw new Exception('Error preparing statement for student_docs: ' . $conn->error);
    }

    // Bind the roll number parameter
    $stmt->bind_param('s', $rollno);

    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception('Error executing statement for student_docs: ' . $stmt->error);
    }

    // Commit the transaction
    $conn->commit();

    // Return success response
    http_response_code(200); // OK
    echo json_encode(['status' => 'success', 'message' => 'Record updated and email sent successfully']);

    // Close the connection
    $conn->close();
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());

    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    $conn->close();
}
?>
