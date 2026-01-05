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
        // Auto-Clock Out Logic
        $date = date('Y-m-d');
        $check_att = "SELECT id, login_time FROM attendance WHERE user_id = '$user_id' AND date = '$date' AND logout_time IS NULL ORDER BY id DESC LIMIT 1";
        $att_result = mysqli_query($conn, $check_att);

        if (mysqli_num_rows($att_result) > 0) {
            $att_row = mysqli_fetch_assoc($att_result);
            $attendance_id = $att_row['id'];
            $login_time = strtotime($att_row['login_time']);
            $logout_time_str = date('H:i:s');
            $logout_time = strtotime($logout_time_str);

            // Calculate total hours
            $duration_seconds = $logout_time - $login_time;
            $total_work_hours = round($duration_seconds / 3600, 2);

            $update_sql = "UPDATE attendance SET logout_time = '$logout_time_str', total_work_hours = '$total_work_hours' WHERE id = '$attendance_id'";
            mysqli_query($conn, $update_sql);
            $_SESSION['message'] = "Daily Log Submitted and Clocked Out Successfully!";
        } else {
            $_SESSION['message'] = "Daily Log Submitted Successfully!";
        }
    } else {
        $_SESSION['error'] = "Error submitting log: " . mysqli_error($conn);
    }

    header("Location: ../employee_dashboard.php");
    exit();
}
?>