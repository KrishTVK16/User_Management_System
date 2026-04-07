<?php
// actions/submit_log.php
session_start();
require('../includes/db_connect.php');
require('../includes/auth_session.php');

check_login();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $date = date('Y-m-d');

    // ENFORCEMENT: Check if clocked in
    $check_clock_in = "SELECT id FROM attendance WHERE user_id = '$user_id' AND date = '$date' AND logout_time IS NULL LIMIT 1";
    $res_clock_in = mysqli_query($conn, $check_clock_in);
    
    if (mysqli_num_rows($res_clock_in) == 0) {
        $_SESSION['error'] = "You must CLOCK IN before submitting a work log!";
        header("Location: ../employee_dashboard.php");
        exit();
    }

    $project_id = $_POST['project_id'] ?? 0;
    $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'In Progress');

    // Parse Time Spent string to minutes
    $time_string = $_POST['time_spent'] ?? '0';
    $time_input = strtolower($time_string);
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

    // Redirect back to project view if id is present, else dashboard
    if ($project_id) {
        header("Location: ../project_view.php?id=$project_id");
    } else {
        header("Location: ../employee_dashboard.php");
    }
    exit();
}
?>