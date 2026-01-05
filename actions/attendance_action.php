<?php
// actions/attendance_action.php
session_start();
require('../includes/db_connect.php');
require('../includes/auth_session.php');

check_login();

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$date = date('Y-m-d');
$now = date('Y-m-d H:i:s');

if ($action == 'clock_in') {
    // Check if already clocked in today
    $check_sql = "SELECT id FROM attendance WHERE user_id = '$user_id' AND date = '$date' AND logout_time IS NULL";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) == 0) {
        $sql = "INSERT INTO attendance (user_id, date, login_time) VALUES ('$user_id', '$date', '$now')";
        if (mysqli_query($conn, $sql)) {
            $_SESSION['message'] = "Clocked in successfully!";
        } else {
            $_SESSION['error'] = "Error clocking in: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "You are already clocked in.";
    }

} elseif ($action == 'clock_out') {
    // Find active attendance record
    $sql = "SELECT id, login_time FROM attendance WHERE user_id = '$user_id' AND logout_time IS NULL ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if ($row = mysqli_fetch_assoc($result)) {
        $attendance_id = $row['id'];
        $login_time = new DateTime($row['login_time']);
        $logout_time = new DateTime($now);

        // Calculate Total Duration in Hours
        $interval = $login_time->diff($logout_time);
        $total_hours = $interval->h + ($interval->i / 60);

        // Subtract Break Times
        $break_sql = "SELECT SUM(duration_minutes) as total_break FROM breaks WHERE attendance_id = '$attendance_id'";
        $break_result = mysqli_query($conn, $break_sql);
        $break_row = mysqli_fetch_assoc($break_result);
        $break_deduction = ($break_row['total_break'] ?? 0) / 60;

        $net_hours = max(0, $total_hours - $break_deduction);

        $update_sql = "UPDATE attendance SET logout_time = '$now', total_work_hours = '$net_hours' WHERE id = '$attendance_id'";
        mysqli_query($conn, $update_sql);
        $_SESSION['message'] = "Clocked out successfully!";
    }

} elseif ($action == 'start_break') {
    // Get current attendance ID
    $sql = "SELECT id FROM attendance WHERE user_id = '$user_id' AND logout_time IS NULL ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if ($row = mysqli_fetch_assoc($result)) {
        $attendance_id = $row['id'];
        $break_sql = "INSERT INTO breaks (attendance_id, start_time, type) VALUES ('$attendance_id', '$now', 'Personal')";
        mysqli_query($conn, $break_sql);
        $_SESSION['message'] = "Break started.";
    }

} elseif ($action == 'end_break') {
    // Find active break
    // We need to join with attendance to ensure it belongs to this user, 
    // or just assume the latest break for this user's active session is enough context if we are strict.
    // Better query: Find the latest open break for an active attendance of this user.

    $sql = "SELECT b.id, b.start_time 
            FROM breaks b 
            JOIN attendance a ON b.attendance_id = a.id 
            WHERE a.user_id = '$user_id' 
            AND b.end_time IS NULL 
            ORDER BY b.id DESC LIMIT 1";

    $result = mysqli_query($conn, $sql);

    if ($row = mysqli_fetch_assoc($result)) {
        $break_id = $row['id'];
        $start_time = new DateTime($row['start_time']);
        $end_time = new DateTime($now);

        $duration = $end_time->getTimestamp() - $start_time->getTimestamp();
        $duration_minutes = round($duration / 60);

        $update_sql = "UPDATE breaks SET end_time = '$now', duration_minutes = '$duration_minutes' WHERE id = '$break_id'";
        mysqli_query($conn, $update_sql);
        $_SESSION['message'] = "Welcome back!";
    }
}

header("Location: ../employee_dashboard.php");
exit();
?>