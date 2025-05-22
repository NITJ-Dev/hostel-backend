<?php
require_once("headers.php");
require_once("db.php");

$data = json_decode(file_get_contents("php://input"));
if (! $data || ! isset($data->action) || ! isset($data->rollno)) {
    http_response_code(400);
    exit(json_encode(['status'=>'error','message'=>'Invalid input']));
}

$me   = $data->rollno;
$act  = strtolower($data->action);
$other = isset($data->other_rollno) ? $data->other_rollno : null;

// helper: check if a roll is already in any pending request
function is_in_request($conn, $roll) {
    $sql = "SELECT 1 FROM roommate_requests_all_year
            WHERE (p1 = ? )
               OR (p2 = ? )
               OR (p3 = ?)
               OR (p4 = ? )";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $roll,$roll,$roll,$roll);
    $stmt->execute();
    $res = $stmt->get_result();
    $in = $res->num_rows > 0;
    $stmt->close();
    return $in;
}

switch ($act) {
  case 'create':
    if (! $other) {
      http_response_code(400);
      exit(json_encode(['status'=>'error','message'=>'Missing other_rollno']));
    }
    // ensure neither party is in any existing request
    if (is_in_request($conn, $me) || is_in_request($conn, $other)) {
      http_response_code(409);
      exit(json_encode(['status'=>'error','message'=>'One of you is already in a request']));
    }
    // insert new request
    $ins = $conn->prepare("
      INSERT INTO roommate_requests_all_year
        (p1,p1_flag,p2,p2_flag)
      VALUES (?,?,?,?)
    ");
    $flag1 = 1; $flag2 = 0;
    $ins->bind_param("ssis", $me,$flag1,$other,$flag2);
    $ok = $ins->execute();
    $ins->close();
    if ($ok) {
      echo json_encode(['status'=>'success','message'=>'Request created']);
    } else {
      http_response_code(500);
      echo json_encode(['status'=>'error','message'=>'DB error']);
    }
    break;

  case 'accept':
    if (! $other) {
      http_response_code(400);
      exit(json_encode(['status'=>'error','message'=>'Missing other_rollno']));
    }
    // locate the request where me is p2 with flag=0
    $sel = $conn->prepare("
      SELECT sno FROM roommate_requests_all_year
      WHERE p2 = ? AND p2_flag = 0
        AND p1 = ?
      LIMIT 1
    ");
    $sel->bind_param("ss",$me,$other);
    $sel->execute();
    $res = $sel->get_result();
    if ($res->num_rows === 0) {
      http_response_code(404);
      exit(json_encode(['status'=>'error','message'=>'No pending request found']));
    }
    $row = $res->fetch_assoc();
    $sno = $row['sno'];
    $sel->close();

    // update p2_flag → 1
    $upd = $conn->prepare("
      UPDATE roommate_requests_all_year
        SET p2_flag = 1
      WHERE sno = ?
    ");
    $upd->bind_param("i",$sno);
    $upd->execute();
    $upd->close();

    echo json_encode(['status'=>'success','message'=>'Request accepted']);
    break;

  case 'revoke':
    // find the request where me is p1 OR p2
    $sel = $conn->prepare("
      SELECT sno, p1_flag, p2_flag
      FROM roommate_requests_all_year
      WHERE (p1 = ? OR p2 = ?)
        AND (p1_flag = 1 OR p2_flag = 1)
      LIMIT 1
    ");
    $sel->bind_param("ss",$me,$me);
    $sel->execute();
    $res = $sel->get_result();
    if ($res->num_rows === 0) {
      http_response_code(404);
      exit(json_encode(['status'=>'error','message'=>'No active request found']));
    }
    $row = $res->fetch_assoc();
    $sno      = $row['sno'];
    $flag1    = $row['p1_flag'];
    $flag2    = $row['p2_flag'];
    $sel->close();

    // if I’m p1, set p1_flag=0; if p2, set p2_flag=0
    if ($me === $row['p1']) {
      $upd = $conn->prepare("
        UPDATE roommate_requests_all_year
          SET p1_flag = 0
        WHERE sno = ?
      ");
    } else {
      $upd = $conn->prepare("
        UPDATE roommate_requests_all_year
          SET p2_flag = 0
        WHERE sno = ?
      ");
    }
    $upd->bind_param("i",$sno);
    $upd->execute();
    $upd->close();

    // if both flags are now zero, delete the row
    if (($flag1 === 1 && $me === $row['p1']   ? 0 : $flag1) === 0
     && ($flag2 === 1 && $me === $row['p2']   ? 0 : $flag2) === 0
    ) {
      $del = $conn->prepare("
        DELETE FROM roommate_requests_all_year
        WHERE sno = ?
      ");
      $del->bind_param("i",$sno);
      $del->execute();
      $del->close();
    }

    echo json_encode(['status'=>'success','message'=>'Request revoked']);
    break;

  default:
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Unknown action']);
}

$conn->close();
