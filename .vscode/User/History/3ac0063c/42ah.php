<?php

function check_step($step){
    if($_SESSION['step'] !== $step ){
        
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Authentication failed, Login again.']);
        
        exit;
    }

}

?>