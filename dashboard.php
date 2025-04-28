<?php
require_once("db.php");

// Initialize variables to hold the counts
$formsFilled = 0;
$documentsUploaded = 0;
$roommateRequests = 0;
$bookings = 0;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Query to count forms filled after 18-07-2024 19:00 PM
    $stmt = $conn->prepare("SELECT COUNT(*) FROM student_form WHERE course='btech' and sem=3 and timestamp > '2024-07-18 18:00:00'");
    $stmt->execute();
    $stmt->bind_result($formsFilled);
    $stmt->fetch();
    $stmt->close();

    // Query to count documents uploaded
    $stmt = $conn->prepare("SELECT COUNT(*) FROM student_docs where timestamp > '2024-07-18 18:00:00'");
    $stmt->execute();
    $stmt->bind_result($documentsUploaded);
    $stmt->fetch();
    $stmt->close();

    // Query to count roommate requests sent
    $stmt = $conn->prepare("SELECT COUNT(*) FROM roommate_requests where request_timestamp  > '2024-07-18 18:00:00'");
    $stmt->execute();
    $stmt->bind_result($roommateRequests);
    $stmt->fetch();
    $stmt->close();

    // Query to count bookings
    $stmt = $conn->prepare("SELECT COUNT(*) FROM booking where timestamp > '2024-07-19 12:15:00'");
    $stmt->execute();
    $stmt->bind_result($bookings);
    $stmt->fetch();
    $stmt->close();
} catch (Exception $e) {
    // Handle error
    error_log('Error: ' . $e->getMessage());
}

// Embed the counts in the HTML using PHP
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand" href="#">Admin Dashboard</a>
</nav>
<div class="container mt-5">
    <h1>Dashboard</h1>
    <div class="row">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-header">Forms Filled</div>
                <div class="card-body">
                    <h5 class="card-title" id="formsFilled"><?php echo $formsFilled; ?></h5>
                    <p class="card-text">Number of Forms Filled </p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-header">Documents Uploaded</div>
                <div class="card-body">
                    <h5 class="card-title" id="documentsUploaded"><?php echo $documentsUploaded; ?></h5>
                    <p class="card-text">Number of documents uploaded.</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-header">Roommate Requests</div>
                <div class="card-body">
                    <h5 class="card-title" id="roommateRequests"><?php echo $roommateRequests; ?></h5>
                    <p class="card-text">Number of roommate requests sent.</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger mb-3">
                <div class="card-header">Bookings</div>
                <div class="card-body">
                    <h5 class="card-title" id="bookings"><?php echo $bookings; ?></h5>
                    <p class="card-text">Number of bookings.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>

