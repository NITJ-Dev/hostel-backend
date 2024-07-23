<?php
require_once("headers.php");
require_once("db.php");
session_start();
// Get the posted data
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
        // Extract data
        $sno = null; // Assuming AUTO_INCREMENT
        $full_name = $data->full_name;
        $rollno = $data->rollno;
        $father_name = $data->father_name;
        $mother_name = $data->mother_name;
        $year = $data->year;
        $branch = $data->branch;
        $physically_handicapped = $data->physically_handicapped;
        $blood_group = $data->blood_group;
        $gender = $data->gender;
        $email = $data->email;
        $self_mobile = $data->self_mobile;
        $father_mobile = $data->father_mobile;
        $mother_mobile = $data->mother_mobile;
        $sibling_mobile = $data->sibling_mobile;
        $guardian_mobile = $data->guardian_mobile;
        $postal_address = $data->postal_address;
        $state = $data->state;
        $local_guardian_address = $data->local_guardian_address;
        $course = $data->course;
        $sem = $data->sem;
        $uploaded = 1;

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400); // Bad Request
            echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
            exit;
        }

        // Check if roll number exists in allowed_rollno table
        $checkAllowedStmt = $conn->prepare("SELECT COUNT(*) FROM allowed_rollno WHERE rollno = ?");
        if ($checkAllowedStmt === false) {
            http_response_code(500); // Internal Server Error
            throw new Exception('Prepare statement failed: ' . $conn->error);
        }
        $checkAllowedStmt->bind_param("s", $rollno);
        $checkAllowedStmt->execute();
        $checkAllowedStmt->bind_result($allowedCount);
        $checkAllowedStmt->fetch();
        $checkAllowedStmt->close();

        // Check if roll number exists in student_login table
        $checkLoginStmt = $conn->prepare("SELECT COUNT(*) FROM student_login WHERE rollno = ?");
        if ($checkLoginStmt === false) {
            http_response_code(500); // Internal Server Error
            throw new Exception('Prepare statement failed: ' . $conn->error);
        }
        $checkLoginStmt->bind_param("s", $rollno);
        $checkLoginStmt->execute();
        $checkLoginStmt->bind_result($loginCount);
        $checkLoginStmt->fetch();
        $checkLoginStmt->close();

        if ($allowedCount == 0 || $loginCount == 0) {
            // Store details in defaulters table
            $defaulterStmt = $conn->prepare("INSERT INTO defaulters (full_name, rollno, father_name, mother_name, year, branch, physically_handicapped, blood_group, gender, email, self_mobile, father_mobile, mother_mobile, sibling_mobile, guardian_mobile, postal_address, state, local_guardian_address, course, sem) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($defaulterStmt === false) {
                http_response_code(500); // Internal Server Error
                throw new Exception('Prepare statement failed: ' . $conn->error);
            }
            $defaulterStmt->bind_param("ssssssssssssssssssss", $full_name, $rollno, $father_name, $mother_name, $year, $branch, $physically_handicapped, $blood_group, $gender, $email, $self_mobile, $father_mobile, $mother_mobile, $sibling_mobile, $guardian_mobile, $postal_address, $state, $local_guardian_address, $course, $sem);

            if ($defaulterStmt->execute()) {
                http_response_code(403); // Forbidden
                echo json_encode(['status' => 'error', 'message' => 'Roll number is not allowed or not registered, details have been recorded.']);
            } else {
                http_response_code(500); // Internal Server Error
                throw new Exception('Failed to store defaulter details: ' . $defaulterStmt->error);
            }

            $defaulterStmt->close();
            exit;
        }

        // Check if the form is already filled by this user
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM student_form WHERE rollno = ?");
        if ($checkStmt === false) {
            http_response_code(500); // Internal Server Error
            throw new Exception('Prepare statement failed: ' . $conn->error);
        }
        $checkStmt->bind_param("s", $rollno);
        $checkStmt->execute();
        $checkStmt->bind_result($count);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($count > 0) {
            http_response_code(409); // Conflict
            echo json_encode(['status' => 'error', 'message' => 'You have already filled out the form.']);
            exit;
        }

        // Prepare the SQL statement
        $stmt = $conn->prepare("INSERT INTO student_form (full_name, rollno, father_name, mother_name, year, branch, physically_handicapped, blood_group, gender, email, self_mobile, father_mobile, mother_mobile, sibling_mobile, guardian_mobile, postal_address, state, local_guardian_address, uploaded, sem, course) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt === false) {
            http_response_code(500); // Internal Server Error
            throw new Exception('Prepare statement failed: ' . $conn->error);
        }

        // Bind parameters
        $stmt->bind_param("ssssssssssssssssssiss", $full_name, $rollno, $father_name, $mother_name, $year, $branch, $physically_handicapped, $blood_group, $gender, $email, $self_mobile, $father_mobile, $mother_mobile, $sibling_mobile, $guardian_mobile, $postal_address, $state, $local_guardian_address, $uploaded, $sem, $course);

        // Execute the statement
        if ($stmt->execute()) {
            http_response_code(200); // OK
            echo json_encode(['status' => 'success', 'message' => 'Registration successful']);
        } else {
            if ($stmt->errno == 1062) { // Duplicate entry
                http_response_code(409); // Conflict
                throw new Exception('Duplicate entry for roll number or email');
            } else {
                http_response_code(500); // Internal Server Error
                throw new Exception('Execute statement failed: ' . $stmt->error);
            }
        }

        // Close the statement
        $stmt->close();
    } else {
        http_response_code(400); // Bad Request
        throw new Exception('Invalid input');
    }
} catch (Exception $e) {
    // Log the exception (this could be to a file, error log, etc.)
    error_log($e->getMessage());

    // Send error message to the frontend
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// Close the connection
$conn->close();
?>

