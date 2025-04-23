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

if (!isset($data['table'], $data['target_column'], $data['new_value'], $data['condition_column'], $data['condition_value'])) {
    echo json_encode(["success" => false, "message" => "Missing parameters"]);
    exit;
}

$table = mysqli_real_escape_string($conn, $data['table']);
$target_column = mysqli_real_escape_string($conn, $data['target_column']);
$new_value = mysqli_real_escape_string($conn, $data['new_value']);
$condition_column = mysqli_real_escape_string($conn, $data['condition_column']);
$condition_value = mysqli_real_escape_string($conn, $data['condition_value']);

$query = "UPDATE `$table` SET `$target_column` = '$new_value' WHERE `$condition_column` = '$condition_value'";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(["success" => false, "message" => "Update failed"]);
    exit;
}

echo json_encode(["success" => true, "message" => "Column updated successfully"]);
?>