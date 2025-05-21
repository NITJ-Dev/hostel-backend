<?php
/**
 * Update the 'step' field for the logged-in student based on session roll number.
 *
 * @param mysqli $conn      Active MySQLi connection
 * @param mixed  $newStep   New step value (must be one of the allowed enum values)
 *
 * @return bool True on success, false on failure or invalid input
 */
function updateStudentStep(mysqli $conn, $newStep): bool {
    // Ensure session is started and roll number is available
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['rollno'])) {
        trigger_error('Missing roll number in session', E_USER_WARNING);
        return false;
    }
    $rollno = $_SESSION['rollno'];

    // Define allowed step values as strings to include decimal ones
    $allowedSteps = ['0', '1', '2', '3', '4', '5.1', '5.2', '6'];

    // Convert to string for strict in_array check
    $stepStr = (string) $newStep;

    // Validate parameter is numeric and in allowed set
    if (!is_numeric($newStep) || !in_array($stepStr, $allowedSteps, true)) {
        trigger_error("Invalid step value: {$stepStr}", E_USER_WARNING);
        return false;
    }

    // Prepare the update statement
    $stmt = $conn->prepare(
        "UPDATE student_login
         SET step = ?
         WHERE rollno = ?"
    );
    if (!$stmt) {
        error_log("Prepare failed: ({$conn->errno}) {$conn->error}");
        return false;
    }

    // Bind parameters and execute
    $stmt->bind_param('ss', $stepStr, $rollno);
    $success = $stmt->execute();
    if (!$success) {
        error_log("Execute failed: ({$stmt->errno}) {$stmt->error}");
    }
    $stmt->close();

    return $success;
}

// Example usage after session and connection are initialized:
// $newStep = $_POST['step'] ?? null;
// if ($newStep !== null) {
//     if (updateStudentStep($conn, $newStep)) {
//         echo json_encode(['status' => 'success', 'message' => "Step updated to {$newStep}"]);
//     } else {
//         echo json_encode(['status' => 'error', 'message' => 'Failed to update step.']);
//     }
// }
?>
