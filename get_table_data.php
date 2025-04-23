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

if (!isset($data['table'])) {
    echo json_encode(["success" => false, "message" => "No table specified"]);
    exit;
}

$table = mysqli_real_escape_string($conn, $data['table']);
$query = "SELECT * FROM `$table`";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(["success" => false, "message" => "Query failed"]);
    exit;
}

$data1 = mysqli_fetch_all($result, MYSQLI_ASSOC);

echo json_encode(["success" => true, "message" => "Table data retrieved", "data" => $data1]);
?>