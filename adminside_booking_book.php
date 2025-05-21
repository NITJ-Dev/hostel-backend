<?php
require_once("headers.php");
require_once("db.php");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

$type = $data["type"];
$hostel = mysqli_real_escape_string($conn, $data["hostel"]);
$floor = mysqli_real_escape_string($conn, $data["floor"]);
$room = mysqli_real_escape_string($conn, $data["room"]);
$firstYr = $data["firstYr"] ? true : false;
$rolls = $data["rollNumbers"];

mysqli_begin_transaction($conn);

$roomTable = $firstYr ? "first_hostel_rooms" : "hostel_rooms";
$studentTable = "first_booking";

// update room
if ($type === "unblock") {
    $updateRoom = "UPDATE $roomTable SET block = 0, block_message = NULL, filled_seats = filled_seats + " . count($rolls) . ", vacant_seats = vacant_seats - " . count($rolls) . " WHERE hostel_name = '$hostel' AND floor_no = '$floor' AND room_no = '$room'";
} else {
    $updateRoom = "UPDATE $roomTable SET filled_seats = filled_seats + " . count($rolls) . ", vacant_seats = vacant_seats - " . count($rolls) . " WHERE hostel_name = '$hostel' AND floor_no = '$floor' AND room_no = '$room'";
}

if (!mysqli_query($conn, $updateRoom)) {
    echo json_encode(["success" => false, "message" => "Room update failed"]);
    exit;
}

// only for first year students: record in booking table
if ($firstYr) {
    // if room update succeeded, continue
    // $id = mysqli_real_escape_string($conn, $rolls[0]);
    $insert = "INSERT INTO $studentTable (application_id, hostel_name, room_no, alloted) VALUES ('$rolls[0]', '$hostel', '$room', 1)";
    if (!mysqli_query($conn, $insert)) {
        mysqli_rollback($conn);
        echo json_encode(["success" => false, "message" => "Student already allotted or booking failed"]);
        exit;
    }
} else {
    if (count($rolls) === 2) {
        $r1 = mysqli_real_escape_string($conn, $rolls[0]);
        $r2 = mysqli_real_escape_string($conn, $rolls[1]);

        // Insert roommate_requests flags
        mysqli_query($conn, "INSERT INTO roommate_requests (requester_rollno, requester_flag, accepter_rollno, accepter_flag) VALUES ('$r1', 1, '$r2', 1)");

        // Insert booking record
        if (!mysqli_query($conn, "INSERT INTO booking (requester_rollno, accepter_rollno, hostel_name, room_no, alloted) VALUES ('$r1', '$r2', '$hostel', '$room', 1)")) {
            mysqli_rollback($conn);
            echo json_encode(["success" => false, "message" => "Students already allotted or booking failed"]);
            exit;
        }
    }
}

mysqli_commit($conn);

echo json_encode(["success" => true, "message" => "room alloted successfully"]);
?>
