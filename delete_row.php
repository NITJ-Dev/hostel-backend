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

if (!isset($data['table']) || !isset($data['rowData'])) {
    echo json_encode(["success" => false, "message" => "Table name or row data not provided"]);
    exit;
}

$table = mysqli_real_escape_string($conn, $data['table']);
$rowData = $data['rowData'];

if (!is_array($rowData) || count($rowData) === 0) {
    echo json_encode(["success" => false, "message" => "Invalid row data"]);
    exit;
}

$conditions = [];
foreach ($rowData as $key => $value) {
    if ($value === null) {
        $conditions[] = "`$key` IS NULL";
    } else {
        $escapedKey = mysqli_real_escape_string($conn, $key);
        $escapedVal = mysqli_real_escape_string($conn, $value);
        $conditions[] = "`$escapedKey` = '$escapedVal'";
    }
}

$whereClause = implode(" AND ", $conditions);
$query = "DELETE FROM `$table` WHERE $whereClause";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(["success" => false, "message" => "Delete query failed"]);
    exit;
}

echo json_encode(["success" => true, "message" => "Row deleted successfully"]);
?>
