<?php
function check_step($expected_step){
    if(!isset($_SESSION['step'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Session expired.']);
        exit;
    }
    
    if(intval($_SESSION['step']) !== intval($expected_step)){
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Bad request.']);
        exit;
    }
}
?>
