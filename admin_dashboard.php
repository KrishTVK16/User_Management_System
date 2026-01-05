<?php
// admin_dashboard.php
session_start();
require('includes/db_connect.php');
require('includes/auth_session.php');

// Ensure user is Admin
check_login();
check_admin();

$date = date('Y-m-d');
$full_name = $_SESSION['full_name'];

// --- METRICS ---

// 1. Total Active Employees (Users with role='employee' and is_active=1)
$emp_count_sql = "SELECT COUNT(*) as total FROM users WHERE role='employee' AND is_active=1";
$emp_count = mysqli_fetch_assoc(mysqli_query($conn, $emp_count_sql))['total'];

// 2. Active Projects
$proj_count_sql = "SELECT COUNT(*) as total FROM projects WHERE status='Active'";
$proj_count = mysqli_fetch_assoc(mysqli_query($conn, $proj_count_sql))['total'];

// 3. Total Hours Logged Today (from attendance)
$hours_sql = "SELECT SUM(total_work_hours) as total_hours FROM attendance WHERE date='$date'";
$hours_result = mysqli_fetch_assoc(mysqli_query($conn, $hours_sql));
$today_hours = round($hours_result['total_hours'] ?? 0, 1);

// --- LIVE MONITOR ---
// Fetch all employees and their latest attendance status for today
$monitor_sql = "SELECT u.full_name, u.username, 
                a.login_time, a.logout_time, a.id as attendance_id
                FROM users u 
                LEFT JOIN attendance a ON u.id = a.user_id AND a.date = '$date'
                WHERE u.role = 'employee' AND u.is_active = 1";
$monitor_result = mysqli_query($conn, $monitor_sql);

// --- RECENT LOGS ---
$logs_sql = "SELECT l.*, u.full_name, p.name as project_name 
             FROM daily_work_logs l 
             JOIN users u ON l.user_id = u.id 
             LEFT JOIN projects p ON l.project_id = p.id
             ORDER BY l.created_at DESC LIMIT 5";
$logs_result = mysqli_query($conn, $logs_sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SmartFusion Team</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
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
                <a href="admin_dashboard.php" class="nav-item active">Dashboard</a>
                <a href="manage_projects.php" class="nav-item">Projects</a>
                <a href="manage_employees.php" class="nav-item">Employees</a>
                <a href="reports.php" class="nav-item">Reports</a>
                <a href="manage_leaves.php" class="nav-item">Leaves & Permissions</a>
                <a href="monthly_evaluation.php" class="nav-item">Evaluations</a>
            </nav>
            <div class="sidebar-header" style="border-top: 1px solid #334155;">
                <a href="logout.php" class="nav-item" style="color: #EF4444;">Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <header class="top-bar">
                <button class="menu-toggle">☰</button>
                <h3>Overview</h3>
                <div class="flex items-center gap-4">
                    <button class="btn btn-outline" style="padding: 0.5rem 1rem;">Export Report</button>
                    <div class="flex items-center gap-2">
                        <div
                            style="width: 32px; height: 32px; background: #3B82F6; border-radius: 50%; color: white; display: flex; align-items: center; justify-content: center;">
                            <?php echo substr($full_name, 0, 1); ?>
                        </div>
                        <span class="text-sm font-semibold">
                            <?php echo $full_name; ?>
                        </span>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="page-content">

                <div class="grid-3">
                    <div class="card stat-card">
                        <h4>Employees Active</h4>
                        <div class="stat-value text-success" style="color: var(--success);">
                            <?php echo $emp_count; ?>
                        </div>
                        <p class="stat-label">Total registered employees</p>
                    </div>

                    <div class="card stat-card">
                        <h4>Active Projects</h4>
                        <div class="stat-value">
                            <?php echo $proj_count; ?>
                        </div>
                        <p class="stat-label">Currently in progress</p>
                    </div>

                    <div class="card stat-card">
                        <h4>Hours Logged</h4>
                        <div class="stat-value" style="color: var(--accent-color);">
                            <?php echo $today_hours; ?>
                        </div>
                        <p class="stat-label">Total hours today</p>
                    </div>
                </div>

                <div class="flex justify-between items-center mb-4">
                    <h3>Live Attendance Monitor (Today)</h3>
                    <div class="flex gap-2">
                        <button class="btn btn-primary" onclick="location.reload()">Refresh</button>
                    </div>
                </div>

                <div class="card mb-8" style="padding: 0;">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Status</th>
                                    <th>Login Time</th>
                                    <th>Activity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($monitor_result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($monitor_result)): ?>
                                        <?php
                                        // Determine Status
                                        $status = '<span class="badge badge-danger">Offline</span>';
                                        $login_display = '-';
                                        $activity = 'No activity today';

                                        if ($row['login_time']) {
                                            $login_display = date('h:i A', strtotime($row['login_time']));

                                            if ($row['logout_time']) {
                                                $status = '<span class="badge" style="background:#E2E8F0; color:#64748B">Clocked Out</span>';
                                                $activity = 'Worked until ' . date('h:i A', strtotime($row['logout_time']));
                                            } else {
                                                // Check for active break
                                                $att_id = $row['attendance_id'];
                                                $bk_sql = "SELECT * FROM breaks WHERE attendance_id='$att_id' AND end_time IS NULL";
                                                $bk_res = mysqli_query($conn, $bk_sql);

                                                if (mysqli_num_rows($bk_res) > 0) {
                                                    $status = '<span class="badge badge-warning">On Break</span>';
                                                    $activity = 'Currently on break';
                                                } else {
                                                    $status = '<span class="badge badge-success">Working</span>';
                                                    $activity = 'Currently online';
                                                }
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="flex items-center gap-2">
                                                    <strong>
                                                        <?php echo htmlspecialchars($row['full_name']); ?>
                                                    </strong>
                                                    <span class="text-sm text-muted">@
                                                        <?php echo htmlspecialchars($row['username']); ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo $status; ?>
                                            </td>
                                            <td>
                                                <?php echo $login_display; ?>
                                            </td>
                                            <td>
                                                <?php echo $activity; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No employees found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <h3>Recent Log Submissions</h3>
                <div class="grid-3 mt-4" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
                    <?php if (mysqli_num_rows($logs_result) > 0): ?>
                        <?php while ($log = mysqli_fetch_assoc($logs_result)): ?>
                            <div class="card">
                                <div class="flex justify-between items-start mb-2">
                                    <strong>
                                        <?php echo htmlspecialchars($log['full_name']); ?>
                                    </strong>
                                    <span class="text-sm text-muted">
                                        <?php echo date('h:i A', strtotime($log['created_at'])); ?>
                                    </span>
                                </div>
                                <p class="text-sm mb-2">Project: <strong>
                                        <?php echo htmlspecialchars($log['project_name']); ?>
                                    </strong></p>
                                <p class="text-muted text-sm">"
                                    <?php echo htmlspecialchars($log['description']); ?>"
                                </p>
                                <div class="flex gap-2 mt-4 items-center">
                                    <span class="badge" style="font-size: 0.7rem; background: #F1F5F9;">
                                        <?php echo $log['status']; ?>
                                    </span>
                                    <span class="text-sm text-muted">Time:
                                        <?php echo $log['time_spent_minutes']; ?>m
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted">No logs submitted yet.</p>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>

</body>

</html>