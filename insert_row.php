<?php
header('Content-Type: application/json');
require_once("headers.php");
require_once("db.php");

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['table']) || !isset($data['row_data'])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

$table = $data['table'];
$rowData = $data['row_data'];

$columns = [];
$values = [];

foreach ($rowData as $key => $value) {
    $columns[] = "`" . mysqli_real_escape_string($conn, $key) . "`";
    if ($value === null) {
        $values[] = "NULL";
    } else {
        $values[] = "'" . mysqli_real_escape_string($conn, $value) . "'";
    }
}

$insertQuery = "INSERT INTO `$table` (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $values) . ")";
$insertResult = mysqli_query($conn, $insertQuery);

if (!$insertResult) {
    echo json_encode(["success" => false, "message" => "Insert query failed"]);
    exit;
}

echo json_encode(["success" => true, "message" => "Row inserted successfully"]);
exit;