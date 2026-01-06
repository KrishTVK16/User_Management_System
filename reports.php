<?php
// reports.php
session_start();
require('includes/db_connect.php');
require('includes/auth_session.php');

check_login();
check_admin();

$full_name = $_SESSION['full_name'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - SmartFusion Team</title>
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
                <div class="text-sm text-muted" style="margin-left: auto;">Admin</div>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="nav-item">Dashboard</a>
                <a href="manage_projects.php" class="nav-item">Projects</a>
                <a href="manage_employees.php" class="nav-item">Employees</a>
                <a href="reports.php" class="nav-item active">Reports</a>
                <a href="manage_leaves.php" class="nav-item">Leaves & Permissions</a>
                <a href="monthly_evaluation.php" class="nav-item">Evaluations</a>
            </nav>
            <div class="sidebar-header" style="border-top: 1px solid #334155;">
                <a href="logout.php" class="nav-item" style="color: #EF4444;">Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <button class="menu-toggle">☰</button>
                <h3>Reports Center</h3>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-semibold">
                        <?php echo $full_name; ?>
                    </span>
                </div>
            </header>

            <div class="page-content">

                <h3 class="mb-4">Export Data</h3>
                <p class="text-muted mb-8">Download comprehensive records in CSV format.</p>

                <div class="grid-3" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">

                    <!-- Attendance Report -->
                    <div class="card">
                        <div class="flex items-center gap-4 mb-4">
                            <div
                                style="width:50px; height:50px; background:#EFF6FF; border-radius:12px; display:flex; align-items:center; justify-content:center; color:var(--primary-color); font-size:1.5rem;">
                                📅
                            </div>
                            <div>
                                <h4>Attendance Report</h4>
                                <p class="text-sm text-muted">Login, logout, and total hours.</p>
                            </div>
                        </div>
                        <a href="export_reports.php?type=attendance" class="btn btn-primary w-full"
                            style="text-decoration:none;">Download CSV</a>
                    </div>

                    <!-- Work Logs Report -->
                    <div class="card">
                        <div class="flex items-center gap-4 mb-4">
                            <div
                                style="width:50px; height:50px; background:#F0FDF4; border-radius:12px; display:flex; align-items:center; justify-content:center; color:var(--success); font-size:1.5rem;">
                                📝
                            </div>
                            <div>
                                <h4>Work Logs Report</h4>
                                <p class="text-sm text-muted">Daily tasks, project hours, and status.</p>
                            </div>
                        </div>
                        <a href="export_reports.php?type=logs" class="btn btn-primary w-full"
                            style="text-decoration:none;">Download CSV</a>
                    </div>

                </div>

            </div>
        </main>
    </div>
</body>

</html>