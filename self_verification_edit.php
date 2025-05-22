<?php
require_once "headers.php";
require_once "db.php";
require_once "verify_student_cookie.php";
require_once "update_step.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $data = json_decode(file_get_contents("php://input"));
    if (! $data) {
        http_response_code(400);
        throw new Exception('Invalid JSON input');
    }

    if (! isset($data->rollno, $data->flag, $data->studentData)) {
        http_response_code(400);
        throw new Exception('Missing required fields'.json_encode($data));
    }

    check_rollno($data->rollno);
    $rollno           = $data->rollno;
    $self_verified    = (int) $data->flag;
    $student_data     = $data->studentData;

    if ($self_verified === 0) {

        // 5) Advance step to 5.1(for roommate of 2nd,3rd and 4th year) and 5.2 for (single room booking for 4th year, and for 1st year.)
        $newStep =  ($student_data->year ==="1") ? "5.2" : "5.1" ;

        if (! updateStudentStep($conn, $newStep)) {
            throw new Exception(json_encode($_SESSION));
        }
        $_SESSION['step'] = $newStep;

        echo json_encode([
            'status'  => 'no_change',
            'message' => 'Response has been saved successfully',
            'step' => $newStep
        ]);
        exit;
    }

    // 1) Update student_form
    $stmt = $conn->prepare(
        "UPDATE student_form
         SET full_name             = ?,
             father_name           = ?,
             mother_name           = ?,
             year                  = ?,
             branch                = ?,
             gender                = ?,
             email                 = ?,
             self_mobile           = ?,
             father_mobile         = ?,
             mother_mobile         = ?,
             sibling_mobile        = ?,
             guardian_mobile       = ?,
             postal_address        = ?,
             state                 = ?,
             local_guardian_address= ?,
             physically_handicapped = ?,
             blood_group           = ?,
             self_verified         = ?
         WHERE rollno = ?"
    );
    if (! $stmt) {
        http_response_code(500);
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        "sssssssssssssssssis",
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

    if (! $stmt->execute()) {
        http_response_code(500);
        throw new Exception("Database error: " . $stmt->error);
    }
    $stmt->close();

    // 5) Advance step to 5.1(for roommate of 2nd,3rd and 4th year) and 5.2 for (single room booking for 4th year, and for 1st year.)
    $newStep =  ($student_data->year ==="1") ? "5.2" : "5.1" ;

    if (! updateStudentStep($conn, $newStep)) {
        throw new Exception(json_encode($_SESSION));
    }
    $_SESSION['step'] = $newStep;

    echo json_encode([
        'status'  => 'success',
        'message' => 'Student details updated successfully',
        'step'    => $newStep
    ]);

    $conn->close();

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    $conn->close();
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}
?>
