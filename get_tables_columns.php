<?php 
require_once("headers.php");
require_once("db.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php_errors.log');

$query = "SELECT table_name, column_name FROM information_schema.columns WHERE table_schema = '$dbname'";

$result = mysqli_query($conn, $query);
if (!$result) {
    echo json_encode(["success" => false, "message" => "unable to get tables"]);
}

$data = mysqli_fetch_all($result, MYSQLI_ASSOC);

echo json_encode(["success" => true, "message" => "all tables returned", "data" => $data]);
?>