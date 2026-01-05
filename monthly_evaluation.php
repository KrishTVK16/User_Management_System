<?php
// monthly_evaluation.php
session_start();
require('includes/db_connect.php');
require('includes/auth_session.php');

check_login();
check_admin();

$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Fetch all users
$users_query = "SELECT id, full_name FROM users WHERE role != 'admin'";
$users_result = mysqli_query($conn, $users_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Evaluation - SmartFusion Team</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .status-undertime {
            color: #DC2626;
            font-weight: 500;
        }

        .status-overtime {
            color: #16A34A;
            font-weight: 500;
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo" style="color: white; font-size: 1.5rem;">SmartFusion Team</div>
                <div class="text-sm text-muted">Admin Panel</div>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="nav-item">Dashboard</a>
                <a href="manage_projects.php" class="nav-item">Projects</a>
                <a href="manage_employees.php" class="nav-item">Employees</a>
                <a href="reports.php" class="nav-item">Reports</a>
                <a href="manage_leaves.php" class="nav-item">Leaves & Permissions</a>
                <a href="monthly_evaluation.php" class="nav-item active">Evaluations</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <button class="menu-toggle">☰</button>
                <h3>Attendance Evaluation</h3>
                <div class="flex items-center gap-4">
                    <!-- User Name or additional functional icons could go here -->
                </div>
            </header>

            <div class="page-content">

                <!-- Filter Control Panel -->
                <div class="card mb-4" style="padding: 1.5rem;">
                    <div class="flex justify-between items-end" style="flex-wrap: wrap; gap: 1rem;">
                        <form action="" method="get" class="flex gap-4 items-end" style="flex-wrap: wrap;">
                            <div class="form-group mb-0">
                                <label class="text-xs text-muted font-bold uppercase tracking-wider">From Date</label>
                                <input type="date" name="start_date" class="form-control"
                                    style="width: auto; padding: 0.5rem;"
                                    value="<?php echo $_GET['start_date'] ?? date('Y-m-01'); ?>">
                            </div>
                            <div class="form-group mb-0">
                                <label class="text-xs text-muted font-bold uppercase tracking-wider">To Date</label>
                                <input type="date" name="end_date" class="form-control"
                                    style="width: auto; padding: 0.5rem;"
                                    value="<?php echo $_GET['end_date'] ?? date('Y-m-d'); ?>">
                            </div>
                            <button type="submit" class="btn btn-primary" style="height: 42px;">Filter Report</button>
                        </form>

                        <form action="export_evaluation.php" method="get">
                            <input type="hidden" name="start_date"
                                value="<?php echo $_GET['start_date'] ?? date('Y-m-01'); ?>">
                            <input type="hidden" name="end_date"
                                value="<?php echo $_GET['end_date'] ?? date('Y-m-d'); ?>">
                            <button type="submit" class="btn btn-success"
                                style="display: flex; align-items: center; gap: 0.5rem; height: 42px;">
                                <span>Download CSV</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Total Days Present</th>
                                    <th>Total Undertime Days</th>
                                    <th>Avg. Daily Hours</th>
                                    <th>Review Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = mysqli_fetch_assoc($users_result)):
                                    $u_id = $user['id'];

                                    $start_date = $_GET['start_date'] ?? date('Y-m-01');
                                    $end_date = $_GET['end_date'] ?? date('Y-m-d');

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

                                        // Calculate duration for the day
                                        $start = strtotime($day['first_login']);
                                        // If no logout recorded (user online or forgot), assume current time for active day or ignore
                                        $end = $day['last_logout'] ? strtotime($day['last_logout']) : time();

                                        $duration = ($end - $start) / 3600;
                                        $total_hours_sum += $duration;

                                        // Flag as Undertime if less than 8.5 hours
                                        if ($duration < 8.5) {
                                            $undertime_days++;
                                        }
                                    }

                                    $avg_hours = $days_present > 0 ? round($total_hours_sum / $days_present, 2) : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo $user['full_name']; ?></td>
                                        <td><?php echo $days_present; ?></td>
                                        <td class="<?php echo $undertime_days > 0 ? 'status-undertime' : ''; ?>">
                                            <?php echo $undertime_days; ?> Days
                                        </td>
                                        <td><?php echo $avg_hours; ?>h</td>
                                        <td>
                                            <?php if ($days_present == 0): ?>
                                                <span class="badge">Absent</span>
                                            <?php elseif ($undertime_days > 3): ?>
                                                <span class="badge badge-danger">Critical</span>
                                            <?php elseif ($undertime_days > 0): ?>
                                                <span class="badge badge-warning">Needs Review</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">Good</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="admin_user_review.php?user_id=<?php echo $u_id; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>"
                                                class="btn btn-primary text-xs">Review</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>