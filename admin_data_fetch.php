<?php
require_once("headers.php");
require_once("db.php");
header('Content-Type: application/json');
// require_once("verify_student_cookie.php");

// Fetch data from student_form
$form_query = "SELECT rollno, clerk_verified, clerk_remarks FROM student_form where self_verified=1 ORDER BY clerk_verified ASC";
$form_result = $conn->query($form_query);

if ($form_result === false) {
    http_response_code(501);
    echo json_encode(array("status" => "error", "message" => "Error fetching student form data: " . $conn->error));
    $conn->close();
    exit();
}

$form_data = [];
while ($row = $form_result->fetch_assoc()) {
    $form_data[] = $row;
}


// Combine the data
$response = [
    "status" => "success",
    "form_data" => $form_data,
    "message" => "Fetched all data"
];

// Send the data to the frontend
echo json_encode($response);

// Close connections
$form_result->close();

$conn->close();
?>
