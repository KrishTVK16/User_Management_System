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

$is_developer = ($user_id == $project['developer_id']);
$is_tester = ($user_id == $project['tester_id']);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['name']); ?> - Project Details</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
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
                <div class="v-stack">
                    <!-- Unified Hero & Stats Section -->
                    <div class="premium-card">
                        <div class="project-hero-content">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="hero-label"><?php echo htmlspecialchars($project['master_name'] ?: 'Standalone Project'); ?></span>
                                    <h1 class="hero-title mt-2"><?php echo htmlspecialchars($project['name']); ?></h1>
                                </div>
                                <div class="status-ring">
                                    <?php echo $project['status']; ?>
                                </div>
                            </div>

                            <p class="text-muted mt-4" style="font-size: 1.1rem; max-width: 800px;">
                                <?php echo nl2br(htmlspecialchars($project['description'])); ?>
                            </p>

                            <div class="hero-meta">
                                <div class="meta-item">
                                    <span class="hero-label" style="font-size: 0.65rem; opacity: 0.7;">Client Detail</span>
                                    <span class="font-bold"><?php echo htmlspecialchars($project['client_name'] ?: 'Internal'); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="hero-label" style="font-size: 0.65rem; opacity: 0.7;">Project Type</span>
                                    <span class="font-bold"><?php echo htmlspecialchars($project['project_type'] ?: 'Static HTML'); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="hero-label" style="font-size: 0.65rem; opacity: 0.7;">Developer</span>
                                    <span class="font-bold text-gold"><?php echo htmlspecialchars($project['developer_name'] ?: 'Unassigned'); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="hero-label" style="font-size: 0.65rem; opacity: 0.7;">Tester</span>
                                    <span class="font-bold text-gold"><?php echo htmlspecialchars($project['tester_name'] ?: 'Unassigned'); ?></span>
                                </div>
                            </div>

                            <!-- Integration Stats directly in Hero -->
                            <div class="hero-meta" style="border-top-style: dashed; padding-top: 1rem;">
                                <div class="meta-item">
                                    <span class="hero-label" style="font-size: 0.6rem;">Assigned</span>
                                    <span class="text-sm"><?php echo $project['assigned_at'] ? date('M d, Y', strtotime($project['assigned_at'])) : '---'; ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="hero-label" style="font-size: 0.6rem;">Started</span>
                                    <span class="text-sm"><?php echo $project['started_at'] ? date('M d, Y', strtotime($project['started_at'])) : '---'; ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="hero-label" style="font-size: 0.6rem;">Completed</span>
                                    <span class="text-sm"><?php echo $project['completed_at'] ? date('M d, Y', strtotime($project['completed_at'])) : '---'; ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="hero-label" style="font-size: 0.6rem;">Current Role</span>
                                    <span class="badge" style="background: var(--primary-color); color: #14202E; font-size: 0.6rem;">
                                        <?php 
                                            if ($is_developer && $is_tester) echo "DEV & TEST";
                                            elseif ($is_developer) echo "DEVELOPER";
                                            elseif ($is_tester) echo "TESTER";
                                            else echo "VIEWER";
                                        ?>
                                    </span>
                                </div>
                            </div>

                            <?php if($project['project_link']): ?>
                                <div class="mt-6">
                                    <a href="<?php echo htmlspecialchars($project['project_link']); ?>" target="_blank" class="btn btn-primary" style="padding: 1rem 2rem; border-radius: 2rem;">
                                        🚀 Launch Live Preview
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Workflow Action Center -->
                    <?php if ($is_developer || $is_tester): ?>
                    <div class="v-stack">
                        <?php if ($is_developer && (in_array($project['status'], ['Assigned', 'Development Initialized', 'Correction Required']))): ?>
                        <div class="form-card" style="border-left: 5px solid var(--primary-color);">
                            <div class="flex items-center gap-3 mb-6">
                                <span style="font-size: 1.5rem;">⚡</span>
                                <h3 class="hero-label" style="color: #fff; margin:0;">Developer Command Center</h3>
                            </div>
                            
                            <?php if ($project['status'] == 'Assigned'): ?>
                                <form action="actions/project_action.php" method="POST" class="v-stack">
                                    <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                                    <input type="hidden" name="action" value="start_development">
                                    <div class="form-group-v2">
                                        <label>Initialization Notes</label>
                                        <textarea name="notes" placeholder="Any initial thoughts before starting?" class="form-control" rows="2"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-full">Initialize & Start development</button>
                                </form>
                            <?php else: ?>
                                <form action="actions/project_action.php" method="POST" class="v-stack">
                                    <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                                    <input type="hidden" name="action" value="complete_development">
                                    <div class="grid-2 gap-6">
                                        <div class="form-group-v2">
                                            <label>Live Preview Link</label>
                                            <input type="url" name="completion_link" class="form-control" required value="<?php echo htmlspecialchars($project['project_link']); ?>">
                                        </div>
                                        <div class="form-group-v2">
                                            <label>Submit for Testing</label>
                                            <button type="submit" class="btn btn-primary w-full">Finalize & Submit</button>
                                        </div>
                                    </div>
                                    <div class="form-group-v2">
                                        <label>Release Notes</label>
                                        <textarea name="notes" placeholder="What have you implemented/fixed?" class="form-control" rows="3" required></textarea>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($is_tester && (in_array($project['status'], ['Testing', 'Corrected']))): ?>
                        <div class="form-card" style="border-left: 5px solid #10B981;">
                            <div class="flex items-center gap-3 mb-6">
                                <span style="font-size: 1.5rem;">🔍</span>
                                <h3 class="hero-label" style="color: #fff; margin:0;">QA Command Center</h3>
                            </div>
                            
                            <div class="flex gap-4">
                                <button onclick="document.getElementById('correction-form').classList.toggle('hidden')" class="btn btn-outline" style="flex: 1;">Request Fixes</button>
                                <form action="actions/project_action.php" method="POST" style="flex: 2;">
                                    <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                                    <input type="hidden" name="action" value="finalize_project">
                                    <button type="submit" class="btn btn-success w-full" onsubmit="return confirm('Finalize project?');">Finalize & Mark as Go-Live</button>
                                </form>
                            </div>

                            <form id="correction-form" action="actions/project_action.php" method="POST" class="v-stack hidden mt-6 p-6 bg-body rounded-lg border border-danger">
                                <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                                <input type="hidden" name="action" value="request_correction">
                                <div class="form-group-v2">
                                    <label class="text-danger">Bug Report / Correction Details</label>
                                    <textarea name="notes" placeholder="Please list all issues that need to be fixed..." class="form-control" rows="4" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger">Submit Correction Request</button>
                            </form>
                        </div>
                        <?php endif; ?>

                        <!-- Work Log - Always Visible for Assigned Users -->
                        <div class="form-card" style="border-left: 5px solid #3B82F6;">
                            <div class="flex items-center gap-3 mb-6">
                                <span style="font-size: 1.5rem;">📝</span>
                                <h3 class="hero-label" style="color: #fff; margin:0;">Daily Productivity Log</h3>
                            </div>
                            <form action="actions/submit_log.php" method="POST" class="v-stack">
                                <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                                <div class="grid-3 gap-6">
                                    <div class="form-group-v2">
                                        <label>Time Spent</label>
                                        <input type="text" name="time_spent" placeholder="e.g. 2h 30m" class="form-control" required>
                                    </div>
                                    <div class="form-group-v2">
                                        <label>Status Update</label>
                                        <select name="status" class="form-control">
                                            <option value="InProgress">In Progress</option>
                                            <option value="FixingBugs">Fixing Bugs</option>
                                            <option value="Optimizing">Optimizing</option>
                                            <option value="Done">Completed</option>
                                        </select>
                                    </div>
                                    <div class="form-group-v2">
                                        <label>Action</label>
                                        <button type="submit" class="btn btn-success w-full">Save Log Entry</button>
                                    </div>
                                </div>
                                <div class="form-group-v2">
                                    <label>Work Description</label>
                                    <textarea name="description" placeholder="Briefly describe what you worked on today..." class="form-control" rows="2" required></textarea>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Journey & Submissions -->
                    <div class="grid-2 gap-8">
                        <div>
                            <h3 class="hero-label mb-4">Project Journey</h3>
                            <div class="premium-card" style="padding: 1.5rem;">
                                <?php if(mysqli_num_rows($history_result) > 0): ?>
                                    <div class="timeline-v3">
                                        <?php while($h = mysqli_fetch_assoc($history_result)): ?>
                                            <div class="timeline-v3-item">
                                                <div class="timeline-v3-icon"></div>
                                                <div class="timeline-v3-card">
                                                    <div class="flex justify-between items-start mb-2">
                                                        <div>
                                                            <div class="text-xs text-muted mb-1"><?php echo date('M d, Y | h:i A', strtotime($h['created_at'])); ?></div>
                                                            <div class="font-bold text-sm"><?php echo htmlspecialchars($h['full_name']); ?></div>
                                                        </div>
                                                        <span class="status-ring" style="font-size: 0.6rem; padding: 0.2rem 0.6rem;"><?php echo $h['to_status']; ?></span>
                                                    </div>
                                                    <div class="text-gold font-bold text-xs uppercase tracking-widest"><?php echo htmlspecialchars($h['action']); ?></div>
                                                    <?php if($h['notes']): ?>
                                                        <p class="text-xs text-muted mt-3 italic border-l-2 border-primary pl-3"><?php echo htmlspecialchars($h['notes']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-10 opacity-50">No history available</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <h3 class="hero-label mb-4">Work Submissions</h3>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Status</th>
                                            <th>Time</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(mysqli_num_rows($logs_result) > 0): ?>
                                            <?php while($l = mysqli_fetch_assoc($logs_result)): ?>
                                                <tr>
                                                    <td>
                                                        <div class="text-xs font-bold"><?php echo htmlspecialchars($l['full_name']); ?></div>
                                                        <div class="text-muted" style="font-size: 0.6rem;"><?php echo date('M d', strtotime($l['date'])); ?></div>
                                                    </td>
                                                    <td><span class="badge" style="font-size: 0.6rem;"><?php echo $l['status']; ?></span></td>
                                                    <td class="text-xs font-mono"><?php echo floor($l['time_spent_minutes'] / 60) . 'h ' . ($l['time_spent_minutes'] % 60) . 'm'; ?></td>
                                                    <td class="text-xs italic opacity-80"><?php echo htmlspecialchars($l['description']); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center py-10 opacity-50">No logs found</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </main>
    </div>

</body>

</html>
