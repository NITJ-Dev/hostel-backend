<?php
require_once("headers.php");
require_once("db.php");

// session_start();

// Get the posted data
$data = json_decode(file_get_contents("php://input"));

if ($data && isset($data->email, $data->password)) {
    $email = $data->email;
    $password = $data->password;

    // Prepare and bind
    $stmt = $conn->prepare("SELECT rollno, password, is_verified, step FROM student_login WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $fetched_data = $result->fetch_assoc();
        $rollno = $fetched_data['rollno'];
        $hashedPassword = $fetched_data['password'];
        $isVerified = $fetched_data['is_verified'];
        $step = $fetch_data['step'];
        
        // Check if roll number belongs to allowed_rollno with course_sem 'btech7'
        // $stmt_course_sem = $conn->prepare("SELECT course_sem FROM allowed_rollno WHERE rollno = ? AND course_sem = 'btech7'");
        $stmt_course_sem = $conn->prepare("SELECT course_sem FROM allowed_rollno WHERE rollno = ?");
        $stmt_course_sem->bind_param("s", $rollno);
        $stmt_course_sem->execute();
        $result_course_sem = $stmt_course_sem->get_result();

        if ($result_course_sem->num_rows > 0) {
            $row_course_sem = $result_course_sem->fetch_assoc();
        
            // Store the course_sem value in a variable
            $course_sem = $row_course_sem['course_sem'];
    
            // Fetch form flag
            $form_flags = $conn->prepare("SELECT uploaded as form_uploaded FROM student_form WHERE rollno = ?");
            $form_flags->bind_param("s", $rollno);
            $form_flags->execute();
            $get_form_flag = $form_flags->get_result();
            if ($get_form_flag->num_rows > 0) {
                $fetch_data = $get_form_flag->fetch_assoc();
                $form_flag = $fetch_data['form_uploaded'];
            } else {
                $form_flag = 0; // Default value if no record found
            }

            // Fetch document flag
            $doc_flags = $conn->prepare("SELECT uploaded as doc_uploaded FROM student_docs WHERE rollno = ?");
            $doc_flags->bind_param("s", $rollno);
            $doc_flags->execute();
            $get_doc_flag = $doc_flags->get_result();
            if ($get_doc_flag->num_rows > 0) {
                $fetch_data = $get_doc_flag->fetch_assoc();
                $doc_flag = $fetch_data['doc_uploaded'];
            } else {
                $doc_flag = 0; // Default value if no record found
            }

            // Verify password and email verification status
            if (hash('sha512', $password) === $hashedPassword) {
                if ($isVerified) {
                    http_response_code(200);
                    $_SESSION["rollno"] = $rollno;
                    $_SESSION["role"] = "student";
                    setcookie(
                        "token_id",
                        hash('sha512', $rollno),
                        [
                            'expires' => time() + 3600*24, // Expires in 24 hour
                            'path' => '/',              // Path
                            // 'domain' => 'v1.nitj.ac.in',   // Domain-of backend
                            'domain' => 'localhost',    // Domain-of backend --comment in production
                            'secure' => false,           // Secure
                            'httponly' => true,         // HTTP-only
                            'samesite' => 'None'        // SameSite attribute
                        ]
                    );
                    echo json_encode(['status' => 'success', 'message' => 'Sign in successful', 'rollno' => $rollno,"course_sem" => $course_sem,'form_uploaded' => $form_flag, 'doc_uploaded' => $doc_flag]);
                } else {
                    http_response_code(403);
                    echo json_encode(['status' => 'error', 'message' => 'Please verify your email!']);
                }
            } else {
                http_response_code(401);
                echo json_encode(['status' => 'error', 'message' => 'Invalid password']);
            }
        } else {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Please signin on your scheduled date.']);
        }

        $form_flags->close();
        $doc_flags->close();
        $stmt_course_sem->close();
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }

    $stmt->close();
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
}

$conn->close();
?>
