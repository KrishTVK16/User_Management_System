<?php
// admin_dashboard.php
session_start();
require('includes/db_connect.php');
require('includes/auth_session.php');

// Ensure user is Admin or Super Admin
check_login();
check_admin();

require('includes/project_functions.php');

$date = date('Y-m-d');
$user_id_session = $_SESSION['user_id'];
$user_role_session = $_SESSION['role'];
$full_name = $_SESSION['full_name'];

// Run automated checks for delays
check_project_delays($conn);

// --- METRICS ---

// 1. Total Active Employees (Users with role='employee' and is_active=1)
$emp_count_sql = "SELECT COUNT(*) as total FROM users WHERE role='employee' AND is_active=1";
$emp_count = mysqli_fetch_assoc(mysqli_query($conn, $emp_count_sql))['total'];

// 2. Active Projects (Those in development or testing)
$proj_count_sql = "SELECT COUNT(*) as total FROM projects WHERE status NOT IN ('Client Submitted', 'Finalized')";
$proj_count = mysqli_fetch_assoc(mysqli_query($conn, $proj_count_sql))['total'];

// 3. Delayed Projects
$delayed_count_sql = "SELECT COUNT(*) as total FROM projects WHERE is_delayed = 1";
$delayed_count = mysqli_fetch_assoc(mysqli_query($conn, $delayed_count_sql))['total'];

// 4. Status Distribution
$status_dist_sql = "SELECT status, COUNT(*) as count FROM projects GROUP BY status";
$status_dist_res = mysqli_query($conn, $status_dist_sql);
$status_data = [];
while ($row = mysqli_fetch_assoc($status_dist_res)) {
    $status_data[$row['status']] = $row['count'];
}

// --- LIVE MONITOR ---
// Fetch all employees and their latest attendance status for today
$monitor_sql = "SELECT u.full_name, u.username, 
                a.login_time, a.logout_time, a.id as attendance_id
                FROM users u 
                LEFT JOIN attendance a ON u.id = a.user_id AND a.date = '$date'
                WHERE u.role = 'employee' AND u.is_active = 1";
$monitor_result = mysqli_query($conn, $monitor_sql);

// --- DELAYED PROJECTS ---
$delayed_sql = "SELECT p.*, u.full_name as dev_name 
                FROM projects p 
                JOIN users u ON p.developer_id = u.id 
                WHERE p.is_delayed = 1 ORDER BY p.started_at ASC";
$delayed_result = mysqli_query($conn, $delayed_sql);

// --- DEVELOPER PRODUCTIVITY ---
$productivity_sql = "SELECT u.full_name, 
                    (SELECT COUNT(*) FROM projects WHERE developer_id = u.id AND DATE(completed_at) = '$date') as completions_today,
                    (SELECT COUNT(*) FROM projects WHERE developer_id = u.id AND status = 'Development Initialized') as active_count
                    FROM users u WHERE u.sub_role IN ('Developer', 'Full Stack') AND u.is_active = 1";
$productivity_result = mysqli_query($conn, $productivity_sql);

// --- ADMIN PERFORMANCE (Super Admin Only) ---
$admin_perf_result = null;
$logs_result = null;
if ($user_role_session == 'super_admin') {
    // Admin Performance
    $admin_perf_sql = "SELECT u.full_name, 
                       (SELECT COUNT(*) FROM project_history WHERE user_id = u.id AND action LIKE '%Assign%' AND DATE(created_at) = '$date') as assignments_today,
                       (SELECT COUNT(*) FROM projects WHERE status = 'Assigned') as pending_assignments
                       FROM users u WHERE u.role = 'admin'";
    $admin_perf_result = mysqli_query($conn, $admin_perf_sql);

    // Recent Logs
    $logs_sql = "SELECT l.*, u.full_name, p.name as project_name 
                 FROM daily_work_logs l 
                 JOIN users u ON l.user_id = u.id 
                 JOIN projects p ON l.project_id = p.id 
                 ORDER BY l.created_at DESC LIMIT 6";
    $logs_result = mysqli_query($conn, $logs_sql);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SmartFusion Team</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css?v=1.1">
</head>

<body>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="assets/logo.png" alt="SmartFusion" class="logo-img">
                <span class="logo-text">SmartFusion</span>
                <div class="text-sm text-muted" style="margin-left: auto;"><?php echo get_role_label($_SESSION['role']); ?></div>
            </div>

            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="nav-item active">Dashboard</a>
                <a href="manage_projects.php" class="nav-item">Projects</a>
                <a href="manage_employees.php" class="nav-item">Employees</a>
                <a href="reports.php" class="nav-item">Reports</a>
                <a href="manage_leaves.php" class="nav-item">Leaves & Permissions</a>
                <a href="monthly_evaluation.php" class="nav-item">Evaluations</a>
                <a href="admin_cleanup.php" class="nav-item" style="color: #EF4444; font-weight: 700; border-left: 0; border-top: 1px solid var(--border-color); padding-top: 1rem; margin-top: 1rem;">System Maintenance</a>
                <a href="logout.php" class="nav-item" style="color: #EF4444; border-left: 0; margin-top: 1rem; border-top: 2px solid var(--border-color); padding-top: 1.5rem;">Logout</a>
            </nav>
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
                            style="width: 32px; height: 32px; background: var(--primary-color); border-radius: var(--radius-sm); color: #14202E; display: flex; align-items: center; justify-content: center; font-weight: 800;">
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

                <div class="grid-4">
                    <div class="card stat-card" style="border-top: 4px solid #3B82F6;">
                        <h4 class="text-sub">Active Projects</h4>
                        <div class="stat-value">
                            <?php echo $proj_count; ?>
                        </div>
                        <p class="text-muted text-sm">In Dev / Testing</p>
                    </div>

                    <div class="card stat-card" style="border-top: 4px solid #EF4444;">
                        <h4 class="text-sub">Delayed</h4>
                        <div class="stat-value" style="color: #EF4444;">
                            <?php echo $delayed_count; ?>
                        </div>
                        <p class="text-muted text-sm">> 2 days in Dev</p>
                    </div>

                    <div class="card stat-card" style="border-top: 4px solid #10B981;">
                        <h4 class="text-sub">Testing</h4>
                        <div class="stat-value">
                            <?php echo $status_data['Testing'] ?? 0; ?>
                        </div>
                        <p class="text-muted text-sm">Awaiting QA</p>
                    </div>

                    <div class="card stat-card" style="border-top: 4px solid var(--primary-color);">
                        <h4 class="text-sub">Corrections</h4>
                        <div class="stat-value">
                            <?php echo $status_data['Correction Required'] ?? 0; ?>
                        </div>
                        <p class="text-muted text-sm">Waiting for fixes</p>
                    </div>
                </div>

                <?php if (mysqli_num_rows($delayed_result) > 0): ?>
                <h3 class="text-danger mt-8 mb-4">Critical Delays (> 2 Days)</h3>
                <div class="grid-3 mb-8">
                    <?php while($dp = mysqli_fetch_assoc($delayed_result)): ?>
                        <div class="card" style="border: 2px solid #EF4444; background: #2A1A1A;">
                            <h4 style="color: #EF4444;"><?php echo htmlspecialchars($dp['name']); ?></h4>
                            <p class="text-sm">Developer: <strong><?php echo htmlspecialchars($dp['dev_name']); ?></strong></p>
                            <p class="text-sm">Status: <span class="text-gold"><?php echo $dp['status']; ?></span></p>
                            <p class="text-sm text-sub">Assigned: <?php echo date('M d, Y', strtotime($dp['assigned_at'])); ?></p>
                        </div>
                    <?php endwhile; ?>
                </div>
                <?php endif; ?>

                <?php if ($user_role_session == 'super_admin'): ?>
                <div class="grid-2 mt-8">
                    <div class="card">
                        <h3>Developer Productivity (Today)</h3>
                        <div class="table-container mt-4">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Developer</th>
                                        <th>Completions</th>
                                        <th>Active Units</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($dev_p = mysqli_fetch_assoc($productivity_result)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($dev_p['full_name']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $dev_p['completions_today'] >= 3 ? 'badge-success' : 'badge-warning'; ?>">
                                                    <?php echo $dev_p['completions_today']; ?> / 3 min
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $dev_p['active_count'] > 6 ? 'badge-danger' : ''; ?>">
                                                    <?php echo $dev_p['active_count']; ?> / 6 max
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php if ($admin_perf_result): ?>
                    <div class="card">
                        <h3>Admin Performance Monitoring</h3>
                        <div class="table-container mt-4">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Admin Name</th>
                                        <th>Assignments (Today)</th>
                                        <th>Action Required</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($adm_p = mysqli_fetch_assoc($admin_perf_result)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($adm_p['full_name']); ?></td>
                                            <td><strong><?php echo $adm_p['assignments_today']; ?></strong></td>
                                            <td>
                                                <span class="text-sm"><?php echo $adm_p['pending_assignments']; ?> unassigned projects</span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <h3>Recent Log Submissions</h3>
                <div class="grid-3 mt-4" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
                    <?php if ($logs_result && mysqli_num_rows($logs_result) > 0): ?>
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
                <?php endif; ?>

            </div>
        </main>
    </div>

</body>

</html>