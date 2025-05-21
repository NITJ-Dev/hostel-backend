<?php
require_once("headers.php");
require_once("db.php");
header('Content-Type: application/json');

if (!isset($_GET['roll'])) {
    echo json_encode(["success" => false, "message" => "Roll number not provided"]);
    exit;
}

$roll = mysqli_real_escape_string($conn, $_GET['roll']);
$table = isset($_GET['table']) ? mysqli_real_escape_string($conn, $_GET['table']) : '';

if ($table === "first_student_form") {
    $query = "SELECT full_name AS name, course, branch, year, application_id AS rollNumber FROM $table WHERE application_id = '$roll'";
} else {
    $query = "SELECT full_name AS name, course, branch, year, rollno AS rollNumber FROM $table WHERE rollno = '$roll'";
}

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    echo json_encode(["success" => false, "message" => "No student found"]);
    exit;
}

$data = mysqli_fetch_assoc($result);
echo json_encode($data);
?>
