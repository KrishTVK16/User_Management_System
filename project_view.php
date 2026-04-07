<?php
// project_view.php
session_start();
require('includes/db_connect.php');
require('includes/auth_session.php');
require('includes/project_functions.php');

check_login();

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$project_id = $_GET['id'] ?? null;

if (!$project_id) {
    header("Location: my_projects.php");
    exit();
}

// Get Project Details
$sql = "SELECT p.*, m.name as master_name, 
               u1.full_name as developer_name, 
               u2.full_name as tester_name 
        FROM projects p 
        LEFT JOIN projects m ON p.parent_id = m.id
        LEFT JOIN users u1 ON p.developer_id = u1.id
        LEFT JOIN users u2 ON p.tester_id = u2.id
        WHERE p.id = '$project_id'";
$result = mysqli_query($conn, $sql);
$project = mysqli_fetch_assoc($result);

if (!$project) {
    header("Location: my_projects.php");
    exit();
}

// Get Project History
$history_sql = "SELECT h.*, u.full_name 
                FROM project_history h 
                JOIN users u ON h.user_id = u.id 
                WHERE h.project_id = '$project_id' 
                ORDER BY h.created_at ASC";
$history_result = mysqli_query($conn, $history_sql);

// Get Project Submissions (Work Logs)
$logs_sql = "SELECT l.*, u.full_name 
             FROM daily_work_logs l 
             JOIN users u ON l.user_id = u.id 
             WHERE l.project_id = '$project_id' 
             ORDER BY l.created_at DESC";
$logs_result = mysqli_query($conn, $logs_sql);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['name']); ?> - Project Details</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css?v=1.1">
    <style>
        .timeline {
            position: relative;
            padding-left: 2rem;
            margin: 2rem 0;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--border-color);
        }
        .timeline-item {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .timeline-dot {
            position: absolute;
            left: -1.85rem;
            top: 0.25rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary-color);
            border: 2px solid var(--bg-card);
        }
        .timeline-content {
            background: var(--bg-body);
            border: 1px solid var(--border-color);
            padding: 1rem;
            border-radius: var(--radius-md);
        }
        .timeline-date {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }
        .timeline-user {
            font-weight: 600;
            font-size: 0.875rem;
        }
        .timeline-action {
            color: var(--primary-color);
            font-weight: 700;
            margin: 0.25rem 0;
        }
        .timeline-status {
            font-size: 0.75rem;
            background: rgba(212, 175, 55, 0.1);
            color: var(--primary-color);
            padding: 0.1rem 0.4rem;
            border-radius: 4px;
        }
    </style>
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
                <a href="my_projects.php" class="nav-item active">My Projects</a>
                <a href="my_leaves.php" class="nav-item">Leaves & Permissions</a>
                <a href="my_history.php" class="nav-item">History</a>
                <a href="profile.php" class="nav-item">Profile</a>
                <a href="logout.php" class="nav-item" style="color: #EF4444; border-left: 0; margin-top: 1rem; border-top: 2px solid var(--border-color); padding-top: 1.5rem;">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <button class="menu-toggle">☰</button>
                <div class="flex items-center gap-2">
                    <a href="my_projects.php" class="text-muted" style="text-decoration: none;">My Projects</a>
                    <span class="text-muted">/</span>
                    <span class="font-bold"><?php echo htmlspecialchars($project['name']); ?></span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-semibold"><?php echo $full_name; ?></span>
                </div>
            </header>

            <div class="page-content">
                <div class="grid-3 mb-8">
                    <div class="card col-span-2">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <span class="text-xs text-primary font-bold uppercase tracking-wider"><?php echo htmlspecialchars($project['master_name'] ?: 'Standalone Project'); ?></span>
                                <h2 class="mt-1"><?php echo htmlspecialchars($project['name']); ?></h2>
                                <p class="text-muted mt-2"><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                            </div>
                            <span class="badge" style="background: var(--bg-body); border: 2px solid var(--primary-color); color: var(--primary-color); padding: 0.5rem 1rem;">
                                <?php echo $project['status']; ?>
                            </span>
                        </div>

                        <div class="grid-2 gap-6">
                            <div class="info-group">
                                <label class="text-xs text-muted font-bold uppercase">Client Name</label>
                                <p class="font-medium"><?php echo htmlspecialchars($project['client_name'] ?: 'Internal'); ?></p>
                            </div>
                            <div class="info-group">
                                <label class="text-xs text-muted font-bold uppercase">Project Type</label>
                                <p class="font-medium"><?php echo htmlspecialchars($project['project_type'] ?: 'Static HTML'); ?></p>
                            </div>
                            <div class="info-group">
                                <label class="text-xs text-muted font-bold uppercase">Developer</label>
                                <p class="font-medium"><?php echo htmlspecialchars($project['developer_name'] ?: 'Not Assigned'); ?></p>
                            </div>
                            <div class="info-group">
                                <label class="text-xs text-muted font-bold uppercase">Tester</label>
                                <p class="font-medium"><?php echo htmlspecialchars($project['tester_name'] ?: 'Not Assigned'); ?></p>
                            </div>
                        </div>

                        <?php if($project['project_link']): ?>
                            <div class="mt-8 pt-6 border-t">
                                <a href="<?php echo htmlspecialchars($project['project_link']); ?>" target="_blank" class="btn btn-primary">Open Live Preview</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card">
                        <h4 class="mb-4">Project Statistics</h4>
                        <div class="flex flex-col gap-4">
                            <div class="stat-item flex justify-between">
                                <span class="text-muted">Assigned Date</span>
                                <span class="font-semibold"><?php echo $project['assigned_at'] ? date('M d, Y', strtotime($project['assigned_at'])) : '---'; ?></span>
                            </div>
                            <div class="stat-item flex justify-between">
                                <span class="text-muted">Started Date</span>
                                <span class="font-semibold"><?php echo $project['started_at'] ? date('M d, Y', strtotime($project['started_at'])) : '---'; ?></span>
                            </div>
                            <div class="stat-item flex justify-between">
                                <span class="text-muted">Completed Date</span>
                                <span class="font-semibold"><?php echo $project['completed_at'] ? date('M d, Y', strtotime($project['completed_at'])) : '---'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid-2 gap-8">
                    <!-- History Timeline -->
                    <div>
                        <h3 class="mb-4">Project History (Workflow)</h3>
                        <div class="card">
                            <?php if(mysqli_num_rows($history_result) > 0): ?>
                                <div class="timeline">
                                    <?php while($h = mysqli_fetch_assoc($history_result)): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-dot"></div>
                                            <div class="timeline-content">
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <div class="timeline-date"><?php echo date('M d, Y | h:i A', strtotime($h['created_at'])); ?></div>
                                                        <div class="timeline-user"><?php echo htmlspecialchars($h['full_name']); ?></div>
                                                    </div>
                                                    <span class="timeline-status"><?php echo $h['to_status']; ?></span>
                                                </div>
                                                <div class="timeline-action uppercase tracking-tighter text-xs"><?php echo htmlspecialchars($h['action']); ?></div>
                                                <?php if($h['notes']): ?>
                                                    <p class="text-xs text-muted italic mt-2 border-l-2 border-primary pl-2"><?php echo htmlspecialchars($h['notes']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-8">No history recorded for this project yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Project Submissions -->
                    <div>
                        <h3 class="mb-4">Internal Submissions (Logs)</h3>
                        <div class="card" style="padding:0;">
                            <table class="table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>User</th>
                                        <th>Status</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($logs_result) > 0): ?>
                                        <?php while($l = mysqli_fetch_assoc($logs_result)): ?>
                                            <tr>
                                                <td class="text-xs">
                                                    <?php echo date('M d', strtotime($l['date'])); ?>
                                                </td>
                                                <td>
                                                    <div class="text-xs font-bold"><?php echo htmlspecialchars($l['full_name']); ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge" style="font-size: 0.6rem;"><?php echo $l['status']; ?></span>
                                                </td>
                                                <td class="text-xs">
                                                    <?php echo floor($l['time_spent_minutes'] / 60) . 'h ' . ($l['time_spent_minutes'] % 60) . 'm'; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="4" class="text-xs text-muted" style="border-top:none; padding-top:0;">
                                                    <?php echo htmlspecialchars($l['description']); ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-8">No work logs submitted for this project.</td>
                                        </tr>
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
