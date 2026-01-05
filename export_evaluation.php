<?php
// export_evaluation.php
session_start();
require('includes/db_connect.php');
require('includes/auth_session.php');

check_login();
check_admin();

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$filename = "Evaluation_Report_{$start_date}_to_{$end_date}.csv";

// Headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// CSV Column Headers
fputcsv($output, array('Employee Name', 'Total Days Present', 'Undertime Days (< 8.5h)', 'Avg Daily Hours', 'Review Status'));

// Fetch all users
$users_query = "SELECT id, full_name FROM users WHERE role != 'admin'";
$users_result = mysqli_query($conn, $users_query);

while ($user = mysqli_fetch_assoc($users_result)) {
    $u_id = $user['id'];

    // Calculate Stats (Same Logic as monthly_evaluation.php)
    $stats_sql = "SELECT date, 
                  MIN(login_time) as first_login, 
                  MAX(logout_time) as last_logout 
                  FROM attendance 
                  WHERE user_id = '$u_id' 
                  AND date >= '$start_date' AND date <= '$end_date'
                  GROUP BY date";
    $stats_result = mysqli_query($conn, $stats_sql);

    $days_present = 0;
    $undertime_days = 0;
    $total_hours_sum = 0;

    while ($day = mysqli_fetch_assoc($stats_result)) {
        $days_present++;
        $start = strtotime($day['first_login']);
        $end = $day['last_logout'] ? strtotime($day['last_logout']) : time();

        $duration = ($end - $start) / 3600;
        $total_hours_sum += $duration;

        if ($duration < 8.5) {
            $undertime_days++;
        }
    }

    $avg_hours = $days_present > 0 ? round($total_hours_sum / $days_present, 2) : 0;

    // Determine Status
    $status = "Good";
    if ($days_present == 0)
        $status = "Absent";
    elseif ($undertime_days > 3)
        $status = "Critical";
    elseif ($undertime_days > 0)
        $status = "Needs Review";

    // Write Row
    fputcsv($output, array($user['full_name'], $days_present, $undertime_days, $avg_hours, $status));
}

fclose($output);
exit();
?>