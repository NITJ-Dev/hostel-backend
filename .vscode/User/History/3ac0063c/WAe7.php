<?php

function check_step($step){
    if($_SESSION['step'] !== $step ){
        
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'You are on wrong page.']);
        
        exit;
    }
}

?>