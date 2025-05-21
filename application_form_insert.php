<?php
// application_form_insert.php

require_once("headers.php");
require_once("db.php");
require_once("is_current_device.php");

require_once("update_step.php");

// Get the posted JSON data
$data = json_decode(file_get_contents("php://input"));

try {
    if (
        $data &&
        isset(
            $data->full_name,
            $data->rollno,
            $data->father_name,
            $data->mother_name,
            $data->year,
            $data->branch,
            $data->physically_handicapped,
            $data->blood_group,
            $data->gender,
            $data->email,
            $data->self_mobile,
            $data->father_mobile,
            $data->mother_mobile,
            $data->sibling_mobile,
            $data->guardian_mobile,
            $data->postal_address,
            $data->state,
            $data->local_guardian_address,
            $data->course,
            $data->sem
        )
    ) {
        // Extract variables
        $full_name              = $data->full_name;
        $rollno                 = $data->rollno;
        $father_name            = $data->father_name;
        $mother_name            = $data->mother_name;
        $year                   = $data->year;
        $branch                 = $data->branch;
        $physically_handicapped = $data->physically_handicapped;
        $blood_group            = $data->blood_group;
        $gender                 = $data->gender;
        $email                  = $data->email;
        $self_mobile            = $data->self_mobile;
        $father_mobile          = $data->father_mobile;
        $mother_mobile          = $data->mother_mobile;
        $sibling_mobile         = $data->sibling_mobile;
        $guardian_mobile        = $data->guardian_mobile;
        $postal_address         = $data->postal_address;
        $state                  = $data->state;
        $local_guardian_address = $data->local_guardian_address;
        $course                 = $data->course;
        $sem                    = $data->sem;
        $uploaded               = 1;


        is_current_device($rollno); 
        //pass the key, that you used while setting this on login ( i mean the roll number for 2-4 year, and for 1st year, application id.)

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400); // Bad Request
            echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
            exit;
        }

        // 1) Check if roll number is allowed
        $stmtAllowed = $conn->prepare("SELECT COUNT(*) FROM allowed_rollno WHERE rollno = ?");
        if (! $stmtAllowed) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        $stmtAllowed->bind_param('s', $rollno);
        $stmtAllowed->execute();
        $stmtAllowed->bind_result($allowedCount);
        $stmtAllowed->fetch();
        $stmtAllowed->close();

        // 2) Check if roll number exists in login
        $stmtLogin = $conn->prepare("SELECT COUNT(*) FROM student_login WHERE rollno = ?");
        if (! $stmtLogin) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        $stmtLogin->bind_param('s', $rollno);
        $stmtLogin->execute();
        $stmtLogin->bind_result($loginCount);
        $stmtLogin->fetch();
        $stmtLogin->close();

        if ($allowedCount == 0 || $loginCount == 0) {
            // Not allowed or not registered: log to defaulters
            $stmtDef = $conn->prepare(
                "INSERT INTO defaulters
                 (full_name, rollno, father_name, mother_name, year, branch,
                  physically_handicapped, blood_group, gender, email,
                  self_mobile, father_mobile, mother_mobile, sibling_mobile,
                  guardian_mobile, postal_address, state, local_guardian_address,
                  course, sem)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            if (! $stmtDef) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            $stmtDef->bind_param(
                'ssssssssssssssssssss',
                $full_name,
                $rollno,
                $father_name,
                $mother_name,
                $year,
                $branch,
                $physically_handicapped,
                $blood_group,
                $gender,
                $email,
                $self_mobile,
                $father_mobile,
                $mother_mobile,
                $sibling_mobile,
                $guardian_mobile,
                $postal_address,
                $state,
                $local_guardian_address,
                $course,
                $sem
            );
            if (! $stmtDef->execute()) {
                throw new Exception('Failed to store defaulter: ' . $stmtDef->error);
            }
            $stmtDef->close();

            http_response_code(403);
            echo json_encode([
                'status' => 'error',
                'message' => 'Roll number not allowed or registered; details recorded.'
            ]);
            exit;
        }

        // 3) Check for existing form
        $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM student_form WHERE rollno = ?");
        if (! $stmtCheck) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        $stmtCheck->bind_param('s', $rollno);
        $stmtCheck->execute();
        $stmtCheck->bind_result($formCount);
        $stmtCheck->fetch();
        $stmtCheck->close();

        if ($formCount > 0) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Form already filled.']);
            exit;
        }

        // 4) Insert into student_form
        $stmtIns = $conn->prepare(
            "INSERT INTO student_form
             (full_name, rollno, father_name, mother_name, year, branch,
              physically_handicapped, blood_group, gender, email,
              self_mobile, father_mobile, mother_mobile, sibling_mobile,
              guardian_mobile, postal_address, state, local_guardian_address,
              uploaded, sem, course)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (! $stmtIns) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        $stmtIns->bind_param(
            'ssssssssssssssssssiss',
            $full_name,
            $rollno,
            $father_name,
            $mother_name,
            $year,
            $branch,
            $physically_handicapped,
            $blood_group,
            $gender,
            $email,
            $self_mobile,
            $father_mobile,
            $mother_mobile,
            $sibling_mobile,
            $guardian_mobile,
            $postal_address,
            $state,
            $local_guardian_address,
            $uploaded,
            $sem,
            $course
        );
        if (! $stmtIns->execute()) {
            if ($stmtIns->errno === 1062) {
                http_response_code(409);
                throw new Exception('Duplicate roll number or email.');
            }
            throw new Exception('Insert failed: ' . $stmtIns->error);
        }
        $stmtIns->close();

        // 5) Advance step to 3
        $newStep = '3';
        if (! updateStudentStep($conn, $newStep)) {
            throw new Exception(json_encode($_SESSION));
        }
        $_SESSION['step'] = $newStep;

        // 6) Success response
        http_response_code(200);
        echo json_encode([
            'status'  => 'success',
            'message' => 'Registration successful',
            'step'    => $newStep
        ]);

    } else {
        http_response_code(400);
        throw new Exception('Missing required fields');
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// Close the connection
$conn->close();
?>

