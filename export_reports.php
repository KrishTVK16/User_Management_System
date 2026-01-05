<?php
// export_reports.php
session_start();
require('includes/db_connect.php');
require('includes/auth_session.php');

check_login();
check_admin();

$type = $_GET['type'] ?? 'attendance';
$date = date('Y-m-d');
$filename = "TeamPulse_{$type}_Report_{$date}.csv";

// Set Headers for Download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

if ($type == 'attendance') {
    // Column Headers
    fputcsv($output, array('Employee ID', 'Name', 'Date', 'Login Time', 'Logout Time', 'Total Hours'));

    // Fetch Data
    $sql = "SELECT u.id as user_id, u.full_name, a.date, a.login_time, a.logout_time, a.total_work_hours 
            FROM attendance a 
            JOIN users u ON a.user_id = u.id 
            ORDER BY a.date DESC";
    $result = mysqli_query($conn, $sql);

    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, $row);
    }
} elseif ($type == 'logs') {
    // Column Headers
    fputcsv($output, array('Log ID', 'Employee', 'Project', 'Date', 'Description', 'Status', 'Minutes Spent'));

    // Fetch Data
    $sql = "SELECT l.id, u.full_name, p.name as project_name, l.date, l.description, l.status, l.time_spent_minutes 
            FROM daily_work_logs l 
            JOIN users u ON l.user_id = u.id 
            LEFT JOIN projects p ON l.project_id = p.id 
            ORDER BY l.date DESC";
    $result = mysqli_query($conn, $sql);

    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, $row);
    }
}

fclose($output);
exit();
?>