<?php
// reports.php
session_start();
require('includes/db_connect.php');
require('includes/auth_session.php');
require('includes/project_functions.php');


check_login();
check_admin();

$full_name = $_SESSION['full_name'];

// Fetch Master Project Progress
$progress_query = "SELECT m.name, 
                          COUNT(p.id) as total_sites,
                          SUM(CASE WHEN p.status = 'Finalized' THEN 1 ELSE 0 END) as completed_sites
                   FROM projects m
                   JOIN projects p ON p.parent_id = m.id
                   WHERE m.parent_id IS NULL
                   GROUP BY m.id
                   ORDER BY m.created_at DESC";
$progress_result = mysqli_query($conn, $progress_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - SmartFusion Team</title>
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
                <a href="admin_dashboard.php" class="nav-item">Dashboard</a>
                <a href="manage_projects.php" class="nav-item">Projects</a>
                <a href="manage_employees.php" class="nav-item">Employees</a>
                <a href="reports.php" class="nav-item active">Reports</a>
                <a href="manage_leaves.php" class="nav-item">Leaves & Permissions</a>
                <a href="monthly_evaluation.php" class="nav-item">Evaluations</a>
                <a href="admin_cleanup.php" class="nav-item" style="color: #EF4444; font-weight: 700; border-left: 0; border-top: 1px solid var(--border-color); padding-top: 1rem; margin-top: 1rem;">System Maintenance</a>
                <a href="logout.php" class="nav-item" style="color: #EF4444; border-left: 0; margin-top: 1rem; border-top: 2px solid var(--border-color); padding-top: 1.5rem;">Logout</a>
            </nav>
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

                <h3 class="mb-4">Project Rollout Progress</h3>
                <div class="grid-1 mb-8">
                    <?php if (mysqli_num_rows($progress_result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($progress_result)): 
                            $percent = $row['total_sites'] > 0 ? round(($row['completed_sites'] / $row['total_sites']) * 100) : 0;
                        ?>
                            <div class="card">
                                <div class="flex justify-between items-center mb-2">
                                    <h4 class="text-gold"><?php echo htmlspecialchars($row['name']); ?></h4>
                                    <span class="text-sm font-bold"><?php echo $row['completed_sites']; ?> / <?php echo $row['total_sites']; ?> Sites Finalized</span>
                                </div>
                                <div style="width: 100%; height: 12px; background: #1B2B3D; border-radius: 6px; overflow: hidden; border: 1px solid var(--border-color);">
                                    <div style="width: <?php echo $percent; ?>%; height: 100%; background: var(--primary-color); transition: width 1s ease-in-out;"></div>
                                </div>
                                <div class="text-right mt-2">
                                    <span class="text-xs text-muted"><?php echo $percent; ?>% Overall Completion</span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="card text-center">
                            <p class="text-muted">No master projects with sub-projects found.</p>
                        </div>
                    <?php endif; ?>
                </div>

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