<?php
/**
 * Update the 'step' field for the logged-in student based on session roll number.
 *
 * @param mysqli     $conn     Active MySQLi connection
 * @param string|int $newStep  New step value (must be one of the allowed enum values)
 * @param string     $rollno   (Optional) Roll number to update; defaults to session rollno
 *
 * @return bool True on success (including “no change”), false on failure or invalid input
 */
function updateStudentStep(mysqli $conn, $newStep, string $rollno = null): bool
{
    // 1) Ensure session is started
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // 2) Pull rollno from session if not explicitly provided
    $sessionRoll = $_SESSION['rollno'] ?? '';
    if (empty($sessionRoll)) {
        trigger_error('Missing roll number in session', E_USER_WARNING);
        return false;
    }
    if ($rollno === null) {
        $rollno = $sessionRoll;
    }

    // 3) If nothing is actually changing, return true immediately
    $currentStep = (string)($_SESSION['step'] ?? '');
    $newStepStr  = (string)$newStep;
    if ($rollno === $sessionRoll && $newStepStr === $currentStep) {
        return true;
    }

    // 4) Validate newStep against allowed list
    $allowedSteps = ['0','1','2','3','4','5.1','5.2','6'];
    if (! in_array($newStepStr, $allowedSteps, true)) {
        trigger_error("Invalid step value: {$newStepStr}", E_USER_WARNING);
        return false;
    }

    // 5) Prepare & execute update
    $stmt = $conn->prepare(
        "UPDATE student_login
            SET step = ?
          WHERE rollno = ?"
    );
    if (! $stmt) {
        error_log("Prepare failed: ({$conn->errno}) {$conn->error}");
        return false;
    }
    $stmt->bind_param('ss', $newStepStr, $rollno);

    $ok = $stmt->execute();
    if (! $ok) {
        error_log("Execute failed: ({$stmt->errno}) {$stmt->error}");
    } else {
        // 6) Update session so future calls see the new step
        $_SESSION['step'] = $newStepStr;
    }

    $stmt->close();
    return $ok;
}

// Example usage:
// session_start();
// require 'db.php';
// if (isset($_POST['step'])) {
//     $result = updateStudentStep($conn, $_POST['step']);
//     echo json_encode([
//         'status'  => $result ? 'success' : 'error',
//         'message' => $result
//             ? \"Step updated to {$_SESSION['step']}\"
//             : 'Failed to update step.'
//     ]);
// }
?>
