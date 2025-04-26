<?php
require_once("headers.php");
require_once("db.php");

$table_name = "student_form";

// Function to get responses based on rollno prefix
function getResponses($search = '') {
    global $conn, $table_name;

    try {
        $query = "SELECT rollno, skip_clerk FROM $table_name WHERE rollno LIKE ?";
        $stmt = $conn->prepare($query);
        $searchTerm = "$search%";
        $stmt->bind_param("s", $searchTerm); // Use "s" (string) for LIKE queries
        $stmt->execute();
        $result = $stmt->get_result(); // Get MySQLi result
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        // Handle the exception (log it, return an empty array, etc.)
        error_log("Error in getResponses: " . $e->getMessage());
        return [["rollno" => $e->getMessage(), "skip_clerk" => 0]];
    }
}

// Function to update the status for a specific rollno
function updateStatus($rollno, $skip_clerk) {
    global $conn, $table_name; // Access the global $conn and $table_name

    try {
        $skip_clerk = (int)$skip_clerk;
        $query = "UPDATE $table_name SET skip_clerk = $skip_clerk WHERE rollno = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $rollno);
        
        if ($stmt->execute()) {
            return true;
        } else {
            throw new Exception(mysqli_error($conn));
        }
    } catch (Exception $e) {
        // Handle the exception (log it, return false, etc.)
        error_log("Error in updateStatus: " . $e->getMessage());
        return false;
    }
}

// Function to update all statuses, based on a prefix or all records
function updateAllStatus($rollno, $skip_clerk) {
    global $conn, $table_name;

    try {
        // Update based on rollno prefix if provided, otherwise update all
        if (!empty($rollno)) {
            // Ensure rollno has a % for prefix search if needed
            $query = "UPDATE $table_name SET skip_clerk = ? WHERE rollno LIKE ?";
            $stmt = $conn->prepare($query);
            $rollno_with_wildcard = $rollno . '%'; // Add wildcard for prefix search
            $stmt->bind_param("is", $skip_clerk, $rollno_with_wildcard);
        } else {
            $query = "UPDATE $table_name SET skip_clerk = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $skip_clerk);
        }

        if ($stmt->execute()) {
            return true;
        } else {
            throw new Exception("Error executing query: " . mysqli_error($conn));
        }
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
            if (!empty($data->rollno) && isset($data->skip_clerk)) {
                $result = updateStatus($data->rollno, $data->skip_clerk);
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
            if (isset($data->skip_clerk)) {
                $rollno = $data->rollno ?? ''; // Optional rollno prefix
                $result = updateAllStatus($rollno, $data->skip_clerk);
                echo json_encode(["success" => $result, "message" => $result ? "All statuses updated." : "Update failed."]);
            } else {
                throw new Exception("Invalid data1.");
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
