<?php
require_once("headers.php");

function unauthorized($a) {
    // Unset all session variables
    $_SESSION = array();
    // If the session cookie is set, delete it
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();

    // // Send unauthorized response
  //  http_response_code(401);
    //echo json_encode(['status' => 'error', 'message' => 'Authentication failed, Login again.'.$a]);
    // // Send unauthorized response
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Authentication failed, Login again.']);
    exit;
}

if (!isset($_SESSION["role"])) {
    unauthorized('1');
}else{
    if($_SESSION["role"] !== "clerk"){
        if(!isset($_COOKIE["token_id"])){
            unauthorized('2');
        }
    }
}

function check_rollno($rollno) {
    // return true;
    if(isset($_SESSION["role"]) ){
        if($_SESSION["role"]==="clerk"){
            return true;
        }else if ($_COOKIE["token_id"] === hash('sha512',$rollno)) { 
            return true;
        }else{
            unauthorized('3');
        }
    }else{
        unauthorized('4');
    }    
}
?>