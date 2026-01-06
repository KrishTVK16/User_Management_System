<?php
// my_history.php
session_start();
require('includes/db_connect.php');
require('includes/auth_session.php');

check_login();

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$show_details = isset($_GET['details']) && $_GET['details'] == 'true';

// 1. Aggregated Attendance (One Row Per Day)
// We group by date to get Min Login and Max Logout
$agg_sql = "SELECT date, 
            MIN(login_time) as first_login, 
            MAX(logout_time) as last_logout, 
            SUM(total_work_hours) as real_work_hours 
            FROM attendance 
            WHERE user_id = '$user_id' 
            GROUP BY date 
            ORDER BY date DESC LIMIT 30";
$agg_result = mysqli_query($conn, $agg_sql);

// 2. Detailed Attendance (For Details View)
$att_sql = "SELECT * FROM attendance WHERE user_id = '$user_id' ORDER BY date DESC, login_time ASC LIMIT 50";
$att_result = mysqli_query($conn, $att_sql);

// 3. Work Logs
$log_sql = "SELECT l.*, p.name as project_name 
            FROM daily_work_logs l 
            LEFT JOIN projects p ON l.project_id = p.id 
            WHERE l.user_id = '$user_id' 
            ORDER BY l.date DESC LIMIT 30";
$log_result = mysqli_query($conn, $log_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My History - SmartFusion Team</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="assets/logo.png" alt="SmartFusion" class="logo-img">
                <span class="logo-text">SmartFusion</span>
            </div>
            <nav class="sidebar-nav">
                <a href="employee_dashboard.php" class="nav-item">Dashboard</a>
                <a href="my_projects.php" class="nav-item">My Projects</a>
                <a href="my_leaves.php" class="nav-item">Leaves & Permissions</a>
                <a href="my_history.php" class="nav-item active">History</a>
                <a href="profile.php" class="nav-item">Profile</a>
            </nav>
            <div class="sidebar-header" style="border-top: 1px solid #334155;">
                <a href="logout.php" class="nav-item" style="color: #EF4444;">Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <button class="menu-toggle">☰</button>
                <h3>My Work History</h3>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-semibold"><?php echo $full_name; ?></span>
                </div>
            </header>

            <div class="page-content">
                
                <div class="flex justify-between items-center mb-4">
                    <h4 class="mb-0">Attendance Overview</h4>
                    <?php if($show_details): ?>
                        <a href="my_history.php" class="btn btn-outline text-sm">Hide Detailed Rows</a>
                    <?php else: ?>
                        <a href="my_history.php?details=true" class="btn btn-outline text-sm">Show Detailed Attendance</a>
                    <?php endif; ?>
                </div>

                <div class="grid-3" style="grid-template-columns: 1.5fr 1fr;">
                    
                    <!-- Attendance History -->
                    <div class="card">
                        <?php if(!$show_details): ?>
                            <!-- Aggregated View (Default) -->
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Day Start</th>
                                            <th>Day End</th>
                                            <th>Total Hours</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(mysqli_num_rows($agg_result) > 0): ?>
                                            <?php while($row = mysqli_fetch_assoc($agg_result)): ?>
                                                <?php
                                                    // Calculate rough duration from first login to last logout
                                                    $start = strtotime($row['first_login']);
                                                    $end = $row['last_logout'] ? strtotime($row['last_logout']) : time();
                                                    $diff = $end - $start;
                                                    $total_h = round($diff / 3600, 2);
                                                    
                                                    // Status styling
                                                    $status_class = ($total_h < 8.5) ? 'text-danger' : 'text-success';
                                                ?>
                                                <tr>
                                                    <td><strong><?php echo date('M d, Y', strtotime($row['date'])); ?></strong></td>
                                                    <td><?php echo date('h:i A', strtotime($row['first_login'])); ?></td>
                                                    <td>
                                                        <?php echo $row['last_logout'] ? date('h:i A', strtotime($row['last_logout'])) : '<span class="badge badge-warning">Active</span>'; ?>
                                                    </td>
                                                    <td class="<?php echo $status_class; ?>">
                                                        <strong><?php echo $total_h; ?>h</strong>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center text-muted">No records found.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <!-- Detailed View -->
                            <h5 class="mb-3 text-muted">Detailed Log Rows</h5>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Login</th>
                                            <th>Logout</th>
                                            <th>Session Hours</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = mysqli_fetch_assoc($att_result)): ?>
                                            <tr>
                                                <td><?php echo date('M d', strtotime($row['date'])); ?></td>
                                                <td><?php echo date('h:i A', strtotime($row['login_time'])); ?></td>
                                                <td>
                                                    <?php echo $row['logout_time'] ? date('h:i A', strtotime($row['logout_time'])) : '-'; ?>
                                                </td>
                                                <td><?php echo $row['total_work_hours']; ?>h</td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Work Log History -->
                    <div class="card">
                        <h4 class="mb-4">Daily Work Logs</h4>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Project</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($log_result) > 0): ?>
                                        <?php while($row = mysqli_fetch_assoc($log_result)): ?>
                                            <tr>
                                                <td><?php echo date('M d', strtotime($row['date'])); ?></td>
                                                <td>
                                                    <div class="text-sm font-semibold"><?php echo htmlspecialchars($row['project_name']); ?></div>
                                                    <div class="text-xs text-muted"><?php echo substr(htmlspecialchars($row['description']), 0, 30) . '...'; ?></div>
                                                </td>
                                                <td><?php echo $row['time_spent_minutes']; ?>m</td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="text-center text-muted">No logs found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            </div>
        </main>
    </div>
</body>
</html>
