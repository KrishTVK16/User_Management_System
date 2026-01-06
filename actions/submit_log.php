<?php
// actions/submit_log.php
session_start();
require('../includes/db_connect.php');
require('../includes/auth_session.php');

check_login();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $project_id = $_POST['project_id'];
    $status = $_POST['status'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $date = date('Y-m-d');

    // Parse Time Spent string to minutes
    $time_input = strtolower($_POST['time_spent']);
    $minutes = 0;

    // Logic: 2h 30m
    if (strpos($time_input, 'h') !== false) {
        $parts = explode('h', $time_input);
        $hours = intval(trim($parts[0]));
        $minutes += $hours * 60;

        if (isset($parts[1]) && strpos($parts[1], 'm') !== false) {
            $m_parts = explode('m', $parts[1]);
            $minutes += intval(trim($m_parts[0]));
        }
    } elseif (strpos($time_input, 'm') !== false) {
        $minutes = intval($time_input);
    } elseif (is_numeric($time_input)) {
        // Assume raw number is minutes if no units
        $minutes = intval($time_input);
    }

    $sql = "INSERT INTO daily_work_logs (user_id, project_id, date, description, status, time_spent_minutes) 
            VALUES ('$user_id', '$project_id', '$date', '$description', '$status', '$minutes')";

    if (mysqli_query($conn, $sql)) {
        $_SESSION['message'] = "Work Log Added Successfully!";
    } else {
        $_SESSION['error'] = "Error submitting log: " . mysqli_error($conn);
    }

    header("Location: ../employee_dashboard.php");
    exit();
}
?>