<?php
require_once("headers.php");
require_once("db.php");
header('Content-Type: application/json');

$table = isset($_GET['table']) ? mysqli_real_escape_string($conn, $_GET['table']) : '';

if ($table !== "hostel_rooms" && $table !== "first_hostel_rooms") {
    echo json_encode(["success" => false, "message" => "Invalid table name"]);
    exit;
}

$query = "SELECT * FROM $table";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(["success" => false, "message" => "Query failed"]);
    exit;
}

$rooms = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rooms[] = $row;
}

echo json_encode($rooms);
?>
