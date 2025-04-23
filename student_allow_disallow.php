<?php
require_once("headers.php");
require_once("db.php");

$table_name = "allowed_students";

function getResponses($search = '') {
    global $conn, $table_name;

    try {
        $query = "SELECT id, rollno, allowed FROM $table_name WHERE rollno LIKE ?";
        $stmt = $conn->prepare($query);
        $searchTerm = "$search%";
        $stmt->bind_param("s", $searchTerm); // Use "s" (string) for LIKE queries
        $stmt->execute();
        $result = $stmt->get_result(); // Get MySQLi result
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        // Handle the exception (log it, return an empty array, etc.)
        error_log("Error in getResponses: " . $e->getMessage());
        return [["id" => 0, "rollno" => $e->getMessage(), "allowed" => 0]];
    }
}

function updateStatus($id, $allowed) {
    global $conn, $table_name; // Access the global $conn and $table_name

    try {
        $allowed = (int)$allowed;
        $query = "UPDATE $table_name SET allowed = $allowed WHERE rollno = $id";
        $result = mysqli_query($conn, $query);
        if (!$result) {
            throw new Exception(mysqli_error($conn));
        }
        return $result;
    } catch (Exception $e) {
        // Handle the exception (log it, return false, etc.)
        error_log("Error in updateStatus: " . $e->getMessage());
        return false;
    }
}

function updateAllStatus($allowed) {
    global $conn, $table_name; // Access the global $conn and $table_name

    try {
        $allowed = (int)$allowed;
        $query = "UPDATE $table_name SET allowed = $allowed";
        $result = mysqli_query($conn, $query);
        if (!$result) {
            throw new Exception(mysqli_error($conn));
        }
        return $result;
    } catch (Exception $e) {
        // Handle the exception (log it, return false, etc.)
        error_log("Error in updateAllStatus: " . $e->getMessage());
        return false;
    }
}

// Enable error reporting and logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php_errors.log'); 

header('Content-Type: application/json'); // Set the response type to JSON

$method = $_SERVER['REQUEST_METHOD'];
$request = $_GET['request'] ?? '';

try {
    if ($method === 'GET' && $request === 'get_responses') {
        try {
            $search = $_GET['search'] ?? '';
            $result = getResponses($search);
            echo json_encode(["success" => true, "data" => $result]);
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Error fetching responses.", "error" => $e->getMessage()]);
        }
    } elseif ($method === 'POST' && $request === 'update_status') {
        try {
            $data = json_decode(file_get_contents("php://input"));
            if (!empty($data->id) && isset($data->allowed)) {
                $result = updateStatus($data->id, $data->allowed);
                echo json_encode(["success" => $result, "message" => $result ? "Status updated." : "Update failed."]);
            } else {
                throw new Exception("Invalid data.");
            }
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
        }
    } elseif ($method === 'POST' && $request === 'update_all_status') {
        try {
            $data = json_decode(file_get_contents("php://input"));
            if (isset($data->allowed)) {
                $result = updateAllStatus($data->allowed);
                echo json_encode(["success" => $result, "message" => $result ? "All statuses updated." : "Update failed."]);
            } else {
                throw new Exception("Invalid data.");
            }
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
        }
    } else {
        throw new Exception("Invalid request.");
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>