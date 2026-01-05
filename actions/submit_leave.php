<?php
// actions/submit_leave.php
session_start();
require('../includes/db_connect.php');
require('../includes/auth_session.php');

check_login();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $type = $_POST['type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'] ?? $start_date; // Default to start date if not set (e.g. for permissions mainly single day)
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);

    // Validate Dates
    if ($start_date > $end_date) {
        $_SESSION['error'] = "Start date cannot be after end date.";
        header("Location: ../my_leaves.php");
        exit();
    }

    $sql = "INSERT INTO leave_requests (user_id, type, start_date, end_date, reason) 
            VALUES ('$user_id', '$type', '$start_date', '$end_date', '$reason')";

    if (mysqli_query($conn, $sql)) {
        $_SESSION['message'] = "Leave/Permission request submitted successfully.";
    } else {
        $_SESSION['error'] = "Error submitting request: " . mysqli_error($conn);
    }

    header("Location: ../my_leaves.php");
    exit();
}
?>