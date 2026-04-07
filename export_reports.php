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
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, $row);
    }
} elseif ($type == 'projects_status') {
    // Column Headers
    fputcsv($output, array('Project Name', 'Client', 'Developer', 'Tester', 'Status', 'Assigned At', 'Started At', 'Completed At', 'Is Delayed'));

    // Fetch Data
    $sql = "SELECT p.name, p.client_name, d.full_name as dev_name, t.full_name as tester_name, p.status, p.assigned_at, p.started_at, p.completed_at, p.is_delayed 
            FROM projects p 
            LEFT JOIN users d ON p.developer_id = d.id 
            LEFT JOIN users t ON p.tester_id = t.id 
            ORDER BY p.status ASC";
    $result = mysqli_query($conn, $sql);

    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, $row);
    }
} elseif ($type == 'productivity') {
    // Column Headers
    fputcsv($output, array('Developer', 'Date', 'Projects Completed Today', 'Projects Currently Initialized'));

    // Fetch Data
    $sql = "SELECT u.full_name, '$date' as date,
            (SELECT COUNT(*) FROM projects WHERE developer_id = u.id AND DATE(completed_at) = '$date') as completions,
            (SELECT COUNT(*) FROM projects WHERE developer_id = u.id AND status = 'Development Initialized') as active_count
            FROM users u WHERE u.sub_role IN ('Developer', 'Full Stack') AND u.is_active = 1";
    $result = mysqli_query($conn, $sql);

    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, $row);
    }
} elseif ($type == 'delays') {
    // Column Headers
    fputcsv($output, array('Project ID', 'Name', 'Client', 'Developer', 'Status', 'Assigned At', 'Delay Reason (Limit > 2 Days)'));

    // Fetch Data
    $sql = "SELECT p.id, p.name, p.client_name, u.full_name as dev_name, p.status, p.assigned_at, 'Time limit exceeded' as reason 
            FROM projects p 
            JOIN users u ON p.developer_id = u.id 
            WHERE p.is_delayed = 1";
    $result = mysqli_query($conn, $sql);

    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, $row);
    }
} elseif ($type == 'daily_logs') {
    // Column Headers
    fputcsv($output, array('Log ID', 'Employee', 'Project', 'Date', 'Work Description', 'Status', 'Minutes Spent'));

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