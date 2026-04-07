<?php
// employee_dashboard.php
session_start();
require('includes/db_connect.php');
require('includes/auth_session.php');

check_login();

require('includes/project_functions.php');

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$user_role = $_SESSION['role'];
$sub_role = $_SESSION['sub_role'] ?? 'None';
$date = date('Y-m-d');

// 1. Get Attendance Status
$query = "SELECT * FROM attendance WHERE user_id = '$user_id' AND date = '$date' AND logout_time IS NULL ORDER BY id DESC LIMIT 1";
$result = mysqli_query($conn, $query);
$attendance = mysqli_fetch_assoc($result);

$is_clocked_in = ($attendance != null);
$attendance_id = $attendance['id'] ?? null;

// Check if on break
$is_on_break = false;
$total_break_minutes = 0;

if ($is_clocked_in) {
    $break_query = "SELECT * FROM breaks WHERE attendance_id = '$attendance_id'";
    $break_result = mysqli_query($conn, $break_query);

    while ($brk = mysqli_fetch_assoc($break_result)) {
        if ($brk['end_time'] == NULL) {
            $is_on_break = true;
        }
        $total_break_minutes += $brk['duration_minutes'];
    }
}

// 2. Get My Projects (Hierarchical)
if ($sub_role == 'Tester') {
    $projects_query = "SELECT p.*, m.name as master_name 
                       FROM projects p 
                       LEFT JOIN projects m ON p.parent_id = m.id
                       WHERE p.tester_id = '$user_id' AND p.status IN ('Testing', 'Corrected') 
                       ORDER BY m.name ASC, p.name ASC";
} else {
    $projects_query = "SELECT p.*, m.name as master_name 
                       FROM projects p 
                       LEFT JOIN projects m ON p.parent_id = m.id
                       WHERE p.developer_id = '$user_id' AND p.status IN ('Assigned', 'Development Initialized', 'Correction Required') 
                       ORDER BY m.name ASC, p.name ASC";
}
$projects_result = mysqli_query($conn, $projects_query);

// 3. Get Notifications
$notifications_query = "SELECT * FROM notifications WHERE user_id = '$user_id' AND is_read = 0 ORDER BY created_at DESC LIMIT 10";
$notifications_result = mysqli_query($conn, $notifications_query);

// 3. Get Today's Logs
$logs_query = "SELECT l.*, p.name as project_name FROM daily_work_logs l 
               LEFT JOIN projects p ON l.project_id = p.id 
               WHERE l.user_id = '$user_id' AND l.date = '$date' 
               ORDER BY l.created_at DESC";
$logs_result = mysqli_query($conn, $logs_query);
// 4. Get First Login of the Day (for 8.5h check)
$first_login_query = "SELECT MIN(login_time) as first_login FROM attendance WHERE user_id = '$user_id' AND date = '$date'";
$first_login_result = mysqli_query($conn, $first_login_query);
$first_login_row = mysqli_fetch_assoc($first_login_result);
$first_login_time = $first_login_row['first_login'] ?? null;

// 5. Fetch Today's Work Logs
$todays_logs_query = "SELECT l.*, p.name as project_name 
                      FROM daily_work_logs l 
                      LEFT JOIN projects p ON l.project_id = p.id 
                      WHERE l.user_id = '$user_id' AND l.date = '$date' 
                      ORDER BY l.created_at DESC";
$todays_logs_result = mysqli_query($conn, $todays_logs_query);
?>
<script>
    const FIRST_LOGIN_TIME = "<?php echo $first_login_time; ?>"; // e.g. 09:00:00 or null
</script>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - SmartFusion Team</title>
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
                <a href="employee_dashboard.php" class="nav-item active">Dashboard</a>
                <a href="my_projects.php" class="nav-item">My Projects</a>
                <a href="my_leaves.php" class="nav-item">Leaves & Permissions</a>
                <a href="my_history.php" class="nav-item">History</a>
                <a href="profile.php" class="nav-item">Profile</a>
                <a href="logout.php" class="nav-item" style="color: #EF4444; border-left: 0; margin-top: 1rem; border-top: 2px solid var(--border-color); padding-top: 1.5rem;">Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <header class="top-bar">
                <button class="menu-toggle">☰</button>
                <h3>My Dashboard</h3>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-muted">
                        <?php echo date('M d, Y'); ?>
                    </span>
                    <div class="flex items-center gap-2">
                        <div
                            style="width: 32px; height: 32px; background: var(--primary-color); border-radius: var(--radius-sm); display:flex; align-items:center; justify-content:center; color:#14202E; font-weight: 800;">
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

                <!-- Flash Messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div
                        style="background-color: #DCFCE7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                        <?php echo $_SESSION['message'];
                        unset($_SESSION['message']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div
                        style="background-color: #2A1A1A; color: #EF4444; border: 2px solid #EF4444; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                        <strong>ERROR:</strong> <?php echo $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Notifications -->
                <?php if (mysqli_num_rows($notifications_result) > 0): ?>
                    <div class="mb-8">
                        <h4 class="mb-2">Alerts & Notifications</h4>
                        <?php while($note = mysqli_fetch_assoc($notifications_result)): ?>
                            <div class="card mb-2" style="padding: 0.75rem 1rem; border-left: 4px solid <?php echo $note['type'] == 'alert' ? '#EF4444' : 'var(--primary-color)'; ?>;">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm"><?php echo htmlspecialchars($note['message']); ?></span>
                                    <span class="text-xs text-sub"><?php echo date('h:i A', strtotime($note['created_at'])); ?></span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>

                <div class="grid-3">
                    <!-- Attendance Widget -->
                    <div class="card stat-card" style="border-top: 4px solid var(--primary-color);">
                        <h4>Attendance</h4>

                        <?php if (!$is_clocked_in): ?>
                            <p class="text-muted text-sm mb-4">You are currently <strong>Clocked Out</strong></p>
                            <form action="actions/attendance_action.php" method="post">
                                <input type="hidden" name="action" value="clock_in">
                                <button type="submit" class="btn btn-primary w-full mt-4">CLOCK IN</button>
                            </form>
                        <?php else: ?>
                            <p class="text-muted text-sm mb-4">Clocked In at <strong>
                                    <?php echo date('h:i A', strtotime($attendance['login_time'])); ?>
                                </strong></p>
                            <form action="actions/attendance_action.php" method="post">
                                <input type="hidden" name="action" value="clock_out">
                                <button type="submit" class="btn btn-danger w-full mt-4">CLOCK OUT</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- Break Widget -->
                    <div class="card stat-card">
                        <h4>Break Status</h4>
                        <?php if (!$is_clocked_in): ?>
                            <p class="text-muted text-sm mb-4">Clock in to take breaks.</p>
                            <button class="btn btn-outline w-full" disabled>Start Break</button>
                        <?php elseif ($is_on_break): ?>
                            <p class="text-muted text-sm mb-4">You are on break.</p>
                            <form action="actions/attendance_action.php" method="post">
                                <input type="hidden" name="action" value="end_break">
                                <button type="submit" class="btn btn-success w-full">END BREAK</button>
                            </form>
                        <?php else: ?>
                            <p class="text-muted text-sm mb-4">Need a breather?</p>
                            <form action="actions/attendance_action.php" method="post">
                                <input type="hidden" name="action" value="start_break">
                                <button type="submit" class="btn btn-outline w-full">Start Break</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($total_break_minutes > 0): ?>
                            <div class="mt-4 pt-4 border-t border-gray-100 text-center">
                                <div class="text-xs text-muted">Total Break Time</div>
                                <div class="font-bold text-lg" style="color: #F59E0B;"><?php echo $total_break_minutes; ?>m
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Role Info Widget -->
                    <div class="card stat-card" style="border-top: 4px solid var(--primary-color);">
                        <h4 class="text-sub">Role & Specialization</h4>
                        <div class="stat-value" style="font-size: 1.5rem; margin: 0.5rem 0;">
                            <?php echo $sub_role; ?>
                        </div>
                        <p class="text-muted text-sm">Assigned as <?php echo $sub_role == 'Tester' ? 'QA/Tester' : 'Project Developer'; ?></p>
                    </div>
                </div>

                <!-- PROJECT WORKFLOW SECTION -->
                <h3 class="mb-4">My Active Project Workflow</h3>
                <div class="grid-1 mb-8">
                    <?php if (mysqli_num_rows($projects_result) > 0): ?>
                        <?php while($p = mysqli_fetch_assoc($projects_result)): ?>
                            <div class="card mb-4">
                                <div class="flex justify-between items-center mb-4">
                                    <div>
                                        <div class="text-xs text-primary font-bold mb-1"><?php echo htmlspecialchars($p['master_name'] ?: 'Standalone Project'); ?></div>
                                        <h4 class="mb-1"><?php echo htmlspecialchars($p['name']); ?></h4>
                                        <div class="flex gap-4">
                                            <span class="text-sm text-muted">Type: <strong><?php echo htmlspecialchars($p['project_type'] ?: 'Static HTML'); ?></strong></span>
                                            <?php if($p['project_link']): ?>
                                                <a href="<?php echo htmlspecialchars($p['project_link']); ?>" target="_blank" class="text-sm text-primary">View Project Link</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="badge" style="background: var(--bg-body); border: 1px solid var(--border-color);"><?php echo $p['status']; ?></span>
                                        <?php if($p['is_delayed']): ?><br><span class="text-xs text-danger font-bold">DELAYED</span><?php endif; ?>
                                    </div>
                                </div>

                                <?php if($p['requirements']): ?>
                                    <div class="mb-4 p-3" style="background: rgba(212, 175, 55, 0.05); border: 1px dashed var(--primary-color); border-radius: var(--radius-sm);">
                                        <div class="text-xs font-bold text-gold mb-1">SPECIFIC REQUIREMENTS:</div>
                                        <div class="text-sm text-main"><?php echo nl2br(htmlspecialchars($p['requirements'])); ?></div>
                                    </div>
                                <?php endif; ?>

                                <div class="p-4 bg-gray-50 border rounded-lg mb-4" style="background: #1B2B3D; padding: 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                                    
                                    <!-- Developer Actions -->
                                    <?php if ($sub_role != 'Tester'): ?>
                                        
                                        <?php if ($p['status'] == 'Assigned'): ?>
                                            <form action="actions/project_action.php" method="post">
                                                <input type="hidden" name="action" value="start_development">
                                                <input type="hidden" name="project_id" value="<?php echo $p['id']; ?>">
                                                <label class="form-label">Initial Notes / Requirements Understanding</label>
                                                <textarea name="notes" class="form-control mb-4" required placeholder="What is required for this project?"></textarea>
                                                <button type="submit" class="btn btn-primary">Initialize Development</button>
                                            </form>
                                        <?php elseif ($p['status'] == 'Development Initialized' || $p['status'] == 'Correction Required'): ?>
                                            
                                            <?php if($p['status'] == 'Correction Required'): ?>
                                                <div style="background:#2A1A1A; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; border: 1px solid #EF4444;">
                                                    <strong class="text-danger">Correction Needed:</strong><br>
                                                    <p class="text-sm"><?php echo htmlspecialchars($p['fix_notes']); ?></p>
                                                </div>
                                            <?php endif; ?>

                                            <form action="actions/project_action.php" method="post">
                                                <input type="hidden" name="action" value="complete_development">
                                                <input type="hidden" name="project_id" value="<?php echo $p['id']; ?>">
                                                <div class="grid-2">
                                                    <div class="form-group">
                                                        <label class="form-label">Staging/Internal URL</label>
                                                        <input type="url" name="completion_link" class="form-control" required placeholder="https://staging.site.com">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Completion Notes</label>
                                                        <textarea name="notes" class="form-control" rows="1" required placeholder="What was done?"></textarea>
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-success">Submit for Testing</button>
                                            </form>
                                        <?php endif; ?>

                                    <!-- Tester Actions -->
                                    <?php else: ?>
                                        
                                        <div class="mb-4">
                                            <strong>Developer:</strong> <?php echo htmlspecialchars($p['completion_notes']); ?><br>
                                            <strong>Link:</strong> <a href="<?php echo htmlspecialchars($p['completion_link']); ?>" target="_blank"><?php echo htmlspecialchars($p['completion_link']); ?></a>
                                        </div>

                                        <div class="flex gap-4">
                                            <button class="btn btn-danger" onclick="document.getElementById('correction-form-<?php echo $p['id']; ?>').style.display='block'">Request Correction</button>
                                            <form action="actions/project_action.php" method="post" style="display:inline;">
                                                <input type="hidden" name="action" value="finalize_project">
                                                <input type="hidden" name="project_id" value="<?php echo $p['id']; ?>">
                                                <button type="submit" class="btn btn-success">Mark as Finalized</button>
                                            </form>
                                        </div>

                                        <div id="correction-form-<?php echo $p['id']; ?>" style="display:none;" class="mt-4 pt-4 border-t">
                                            <form action="actions/project_action.php" method="post">
                                                <input type="hidden" name="action" value="request_correction">
                                                <input type="hidden" name="project_id" value="<?php echo $p['id']; ?>">
                                                <label class="form-label text-danger">Correction Notes</label>
                                                <textarea name="notes" class="form-control" rows="3" required placeholder="Specify what needs to be fixed..."></textarea>
                                                <button type="submit" class="btn btn-danger mt-2">Send Back to Developer</button>
                                            </form>
                                        </div>

                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="card text-center py-8">
                            <p class="text-muted">No projects currently awaiting your action.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <h3 class="mb-4">Submit Daily Work Log</h3>
                <div class="card mb-4">
                    <form action="actions/submit_log.php" method="post" onsubmit="return validateWorkHours(event)">
                        <div class="flex gap-4" style="flex-wrap: wrap;">
                            <div class="form-group" style="flex: 1; min-width: 200px;">
                                <label class="form-label">Project</label>
                                <select name="project_id" class="form-control" required>
                                    <option value="">Select a project...</option>
                                    <?php
                                    mysqli_data_seek($projects_result, 0);
                                    $current_master = "";
                                    while ($proj = mysqli_fetch_assoc($projects_result)): 
                                        if ($proj['master_name'] !== $current_master) {
                                            if ($current_master !== "") echo "</optgroup>";
                                            $current_master = $proj['master_name'] ?: 'General Projects';
                                            echo "<optgroup label='" . htmlspecialchars($current_master) . "'>";
                                        }
                                    ?>
                                        <option value="<?php echo $proj['id']; ?>">
                                            <?php echo htmlspecialchars($proj['name']); ?>
                                        </option>
                                    <?php endwhile; 
                                    if ($current_master !== "") echo "</optgroup>";
                                    ?>
                                </select>
                            </div>
                            <div class="form-group" style="flex: 1; min-width: 200px;">
                                <label class="form-label">Task Status</label>
                                <select name="status" class="form-control">
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Blocked">Blocked</option>
                                </select>
                            </div>
                            <div class="form-group" style="width: 150px;">
                                <label class="form-label">Time Spent</label>
                                <input type="text" name="time_spent" class="form-control" placeholder="e.g. 2h 30m"
                                    required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Work Description</label>
                            <textarea name="description" class="form-control" rows="3"
                                placeholder="Describe what you worked on..." required></textarea>
                        </div>

                        <div class="text-right" style="text-align: right;">
                            <button type="submit" class="btn btn-primary">Add Work Log</button>
                        </div>
                    </form>
                </div>

                <!-- NEW SECTION: Today's Logs -->
                <h3 class="mb-4">Today's Work Logs</h3>
                <div class="card">
                    <table class="table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Time</th>
                                <th style="text-align: right;">Logged At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($todays_logs_result) > 0): ?>
                                <?php while ($log = mysqli_fetch_assoc($todays_logs_result)): ?>
                                    <tr>
                                        <td><span
                                                class="font-semibold"><?php echo htmlspecialchars($log['project_name']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['description']); ?></td>
                                        <td><span
                                                class="badge badge-<?php echo strtolower(str_replace(' ', '-', $log['status'])); ?>"><?php echo $log['status']; ?></span>
                                        </td>
                                        <td><?php echo floor($log['time_spent_minutes'] / 60) . 'h ' . ($log['time_spent_minutes'] % 60) . 'm'; ?>
                                        </td>
                                        <td style="text-align: right;" class="text-muted text-sm">
                                            <?php echo date('h:i A', strtotime($log['created_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted" style="padding: 2rem;">No logs submitted
                                        today.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <script>
                    function validateWorkHours(e) {
                        if (!FIRST_LOGIN_TIME) return true; // No login time found, let it pass (or handle error)

                        const now = new Date();
                        const loginTimeParts = FIRST_LOGIN_TIME.split(':');
                        const loginDate = new Date();
                        loginDate.setHours(parseInt(loginTimeParts[0]), parseInt(loginTimeParts[1]), parseInt(loginTimeParts[2]));

                        const diffMs = now - loginDate;
                        const diffHours = diffMs / (1000 * 60 * 60);

                        // 8.5 Hours Check
                        if (diffHours < 8.5) {
                            const confirmMsg = "Warning: You have worked less than 8.5 hours (" + diffHours.toFixed(2) + " hours).\n\nThis will be recorded as Missing Hours.\n\nAre you sure you want to submit and clock out?";
                            return confirm(confirmMsg);
                        }
                        return true;
                    }
                </script>

                <h3 class="mb-4">Today's Activity</h3>
                <div class="card" style="padding: 0;">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Description</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($logs_result) > 0): ?>
                                    <?php while ($log = mysqli_fetch_assoc($logs_result)): ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($log['project_name']); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($log['description']); ?>
                                            </td>
                                            <td>
                                                <?php echo $log['time_spent_minutes']; ?>m
                                            </td>
                                            <td>
                                                <?php
                                                $badge_class = 'badge-warning'; // Default In Progress
                                                if ($log['status'] == 'Completed')
                                                    $badge_class = 'badge-success';
                                                if ($log['status'] == 'Blocked')
                                                    $badge_class = 'badge-danger';
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo $log['status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No work logged yet today.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

</body>

</html>