<?php
// Enable error reporting (for debugging, disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

require_once("headers.php");
require_once("db.php");

// Helper to sanitize input
function sanitize($conn, $data) {
    return mysqli_real_escape_string($conn, trim($data));
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!isset($_GET['rollno'])) {
            throw new Exception("Roll number is required");
        }

        $rollno = sanitize($conn, $_GET['rollno']);
        $sql = "SELECT * FROM student_form WHERE rollno = '$rollno'";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            echo json_encode($row);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "No student found with the provided roll number"]);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['rollno'])) {
            throw new Exception("Roll number is required");
        }

        $rollno = sanitize($conn, $data['rollno']);

        $fields = [
            'full_name', 'father_name', 'mother_name', 'course', 'sem', 'year',
            'branch', 'physically_handicapped', 'blood_group', 'gender',
            'email', 'self_mobile', 'father_mobile', 'mother_mobile'
        ];

        $set = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $value = sanitize($conn, $data[$field]);
                $set[] = "$field = '$value'";
            }
        }

        if (empty($set)) {
            throw new Exception("No fields to update");
        }

        $sql = "UPDATE student_form SET " . implode(", ", $set) . " WHERE rollno = '$rollno'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_affected_rows($conn) > 0) {
            echo json_encode(["success" => true, "message" => "Student profile updated successfully"]);
        } else {
            // Check if student exists
            $check = mysqli_query($conn, "SELECT * FROM student_form WHERE rollno = '$rollno'");
            if (mysqli_num_rows($check) > 0) {
                echo json_encode(["success" => true, "message" => "No changes made"]);
            } else {
                http_response_code(404);
                echo json_encode(["error" => "Student not found"]);
            }
        }

    } else {
        http_response_code(405);
        echo json_encode(["error" => "Invalid request method"]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["error" => $e->getMessage()]);
}
