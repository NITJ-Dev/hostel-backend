<?php
require_once("headers.php");
require_once("db.php");
require_once("verify_student_cookie.php");

// Function to handle file upload
function uploadFile($file, $directory, $filename) {
    $target_dir = $directory;
    $target_file = $target_dir . $filename;
    $uploadOk = 1;
    $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check file size
    if ($file["size"] > 500000) { // 500kb limit
        return ["success" => false, "message" => "Sorry, your file is too large."];
    }

    // Allow certain file formats
    if ($fileType != "jpg" && $fileType != "png" && $fileType != "jpeg" && $fileType != "pdf") {
        return ["success" => false, "message" => "Sorry, only JPG, JPEG, PNG & PDF files are allowed."];
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        return ["success" => false, "message" => "Sorry, your file was not uploaded."];
    // if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            return ["success" => true, "filePath" => $target_file];
        } else {
            return ["success" => false, "message" => "Sorry, there was an error uploading your file."];
        }
    }
}

// Ensure directories exist
$directories = [
    'hostelReceipt' => 'uploads/hostelReceipt/',
    'messAdvance' => 'uploads/messAdvance/',
    'aadhaar' => 'uploads/aadhaar/',
    'photos' => 'uploads/photos/'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Set default response code to 400 (Bad Request)
http_response_code(400);

// Get the posted data
if (isset($_POST['rollno']) && isset($_FILES['hostelReceipt']) && isset($_FILES['messAdvance']) && isset($_FILES['aadhaar']) && isset($_FILES['photos'])) {
    $rollno = $_POST['rollno'];

    $hostelReceiptFile = $_FILES['hostelReceipt'];
    $messAdvanceFile = $_FILES['messAdvance'];
    $aadhaarFile = $_FILES['aadhaar'];
    $photosFile = $_FILES['photos'];

    // Generate random file names
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $random_code1 = substr(str_shuffle($chars), 0, 32);
    $random_code2 = substr(str_shuffle($chars), 0, 32);
    $random_code3 = substr(str_shuffle($chars), 0, 32);
    $random_code4 = substr(str_shuffle($chars), 0, 32);

    // Construct file paths
    $hostelReceiptPath = $random_code1 . ".pdf";
    $messAdvancePath = $random_code2 . ".pdf";
    $aadhaarPath = $random_code3 . ".pdf";
    $photosPath = $random_code4 . ".jpg";

    // Upload files and get paths
    $hostelReceiptResult = uploadFile($hostelReceiptFile, $directories['hostelReceipt'], $hostelReceiptPath);
    $messAdvanceResult = uploadFile($messAdvanceFile, $directories['messAdvance'], $messAdvancePath);
    $aadhaarResult = uploadFile($aadhaarFile, $directories['aadhaar'], $aadhaarPath);
    $photosResult = uploadFile($photosFile, $directories['photos'], $photosPath);

    // Check if any file upload failed
    if (!$hostelReceiptResult['success'] || !$messAdvanceResult['success'] || !$aadhaarResult['success'] || !$photosResult['success']) {
        echo json_encode(['status' => 'error', 'message' => 'File upload error.']);
        exit;
    }

    $uploaded = 1;

    // Prepare the SQL statement
    $stmt = $conn->prepare("
    INSERT INTO student_docs (rollno, institute_fee_receipt, mess_advance_receipt, aadhar_card, student_photo, uploaded)
    VALUES (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
    institute_fee_receipt = VALUES(institute_fee_receipt),
    mess_advance_receipt = VALUES(mess_advance_receipt),
    aadhar_card = VALUES(aadhar_card),
    student_photo = VALUES(student_photo),
    uploaded = VALUES(uploaded)
    ");

    if ($stmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Prepare statement failed: ' . $conn->error]);
        exit;
    }

    // Bind parameters
    $stmt->bind_param("sssssi", $rollno, $hostelReceiptResult['filePath'], $messAdvanceResult['filePath'], $aadhaarResult['filePath'], $photosResult['filePath'], $uploaded);

    // Execute the statement
    if ($stmt->execute()) {
        // Set success response code to 200 (OK)
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Documents uploaded successfully']);
    } else {
        if ($stmt->errno == 1062) { // Duplicate entry
            // Set conflict response code to 409 (Conflict)
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Duplicate entry for roll number']);
        } else {
            // Set internal server error response code to 500 (Internal Server Error)
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Execute statement failed: ' . $stmt->error]);
        }
    }

    // Close the statement
    $stmt->close();
}
elseif (isset($_POST['rollno']) && isset($_FILES['messAdvance']) && isset($_FILES['aadhaar']) && isset($_FILES['photos'])) {
    $rollno = $_POST['rollno'];

    //$hostelReceiptFile = $_FILES['hostelReceipt'];
    $messAdvanceFile = $_FILES['messAdvance'];
    $aadhaarFile = $_FILES['aadhaar'];
    $photosFile = $_FILES['photos'];

    // Generate random file names
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    //$random_code1 = substr(str_shuffle($chars), 0, 32);
    $random_code2 = substr(str_shuffle($chars), 0, 32);
    $random_code3 = substr(str_shuffle($chars), 0, 32);
    $random_code4 = substr(str_shuffle($chars), 0, 32);

    // Construct file paths
    //$hostelReceiptPath = $random_code1 . ".pdf";
    $messAdvancePath = $random_code2 . ".pdf";
    $aadhaarPath = $random_code3 . ".pdf";
    $photosPath = $random_code4 . ".jpg";

    // Upload files and get paths
    //$hostelReceiptResult = uploadFile($hostelReceiptFile, $directories['hostelReceipt'], $hostelReceiptPath);
    $messAdvanceResult = uploadFile($messAdvanceFile, $directories['messAdvance'], $messAdvancePath);
    $aadhaarResult = uploadFile($aadhaarFile, $directories['aadhaar'], $aadhaarPath);
    $photosResult = uploadFile($photosFile, $directories['photos'], $photosPath);

    // Check if any file upload failed
    if (!$messAdvanceResult['success'] || !$aadhaarResult['success'] || !$photosResult['success']) {
        echo json_encode(['status' => 'error', 'message' => 'File upload error.']);
        exit;
    }

    $uploaded = 1;

    // Prepare the SQL statement
    $stmt = $conn->prepare("
    INSERT INTO student_docs (rollno, institute_fee_receipt, mess_advance_receipt, aadhar_card, student_photo, uploaded)
    VALUES (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
    institute_fee_receipt = VALUES(institute_fee_receipt),
    mess_advance_receipt = VALUES(mess_advance_receipt),
    aadhar_card = VALUES(aadhar_card),
    student_photo = VALUES(student_photo),
    uploaded = VALUES(uploaded)
    ");

    if ($stmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Prepare statement failed: ' . $conn->error]);
        exit;
    }

    $hostelReceiptResult['filePath']=NULL;  
    // Bind parameters
    $stmt->bind_param("sssssi", $rollno, $hostelReceiptResult['filePath'], $messAdvanceResult['filePath'], $aadhaarResult['filePath'], $photosResult['filePath'], $uploaded);

    // Execute the statement
    if ($stmt->execute()) {
        // Set success response code to 200 (OK)
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Documents uploaded successfully']);
    } else {
        if ($stmt->errno == 1062) { // Duplicate entry
            // Set conflict response code to 409 (Conflict)
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Duplicate entry for roll number']);
        } else {
            // Set internal server error response code to 500 (Internal Server Error)
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Execute statement failed: ' . $stmt->error]);
        }
    }

    // Close the statement
    $stmt->close();
}
 else {
    // Set error response code to 400 (Bad Request) for invalid input
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
}

// Close the connection
$conn->close();
?>

