<?php
require_once 'RedisLoginManager.php';

function is_current_device($rollno, $deviceID){

    $manager = new RedisLoginManager();
    $storedId = $manager->get($rollno);

    if ($storedId && ($storedId === $deviceId)) {
        return true;
    } 
    http_response_code(404); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Your session is old, please re-login.']);
    exit;
}