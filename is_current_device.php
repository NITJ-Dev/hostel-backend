<?php
require_once 'RedisLoginManager.php';

function is_current_device($key){

    $deviceId = $_COOKIE["PHPSESSID"];

    $manager = new RedisLoginManager();
    $storedId = $manager->get($key);

    if ($storedId && ($storedId === $deviceId)) {
        return true;
    } 
    http_response_code(404); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Your session is old, please re-login.']);
    exit;
}