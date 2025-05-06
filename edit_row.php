<?php
require_once("headers.php");
require_once("db.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php_errors.log');

header('Content-Type: application/json');
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['table']) || !isset($data['row_data']) || !isset($data['original_data'])) {
    echo json_encode(["success" => false, "message" => "Missing parameters"]);
    exit;
}

$table = mysqli_real_escape_string($conn, $data['table']);
$rowData = $data['row_data'];
$originalData = $data['original_data'];

// Prepare SET clause
$set = [];
foreach ($rowData as $col => $val) {
    $safeCol = mysqli_real_escape_string($conn, $col);
    $safeVal = isset($val) ? "'" . mysqli_real_escape_string($conn, $val) . "'" : "NULL";
    $set[] = "`$safeCol` = $safeVal";
}
$setClause = implode(", ", $set);

// Prepare WHERE clause from original values
$where = [];
foreach ($originalData as $col => $val) {
    $safeCol = mysqli_real_escape_string($conn, $col);
    if (is_null($val)) {
        $where[] = "`$safeCol` IS NULL";
    } else {
        $safeVal = mysqli_real_escape_string($conn, $val);
        $where[] = "`$safeCol` = '$safeVal'";
    }
}
$whereClause = implode(" AND ", $where);

$query = "UPDATE `$table` SET $setClause WHERE $whereClause LIMIT 1";

if (mysqli_query($conn, $query)) {
    echo json_encode(["success" => true, "message" => "Row updated successfully", "query" => $query]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to update row"]);
}
?>
