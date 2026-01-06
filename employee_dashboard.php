<?php
// employee_dashboard.php
session_start();
require('includes/db_connect.php');
require('includes/auth_session.php');

check_login();

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
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

// 2. Get Assigned Projects
$projects_query = "SELECT p.id, p.name FROM projects p 
                   JOIN project_assignments pa ON p.id = pa.project_id 
                   WHERE pa.user_id = '$user_id' AND p.status = 'Active'";
// For demo purposes, if no assignments, show all active projects so the user sees something
if (mysqli_num_rows(mysqli_query($conn, $projects_query)) == 0) {
    $projects_query = "SELECT id, name FROM projects WHERE status = 'Active'";
}
$projects_result = mysqli_query($conn, $projects_query);

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
                <h3>My Dashboard</h3>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-muted">
                        <?php echo date('M d, Y'); ?>
                    </span>
                    <div class="flex items-center gap-2">
                        <div
                            style="width: 32px; height: 32px; background: #E2E8F0; border-radius: 50%; display:flex; align-items:center; justify-content:center; color:#64748B;">
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
                        style="background-color: #FEE2E2; color: #991B1B; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                        <?php echo $_SESSION['error'];
                        unset($_SESSION['error']); ?>
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

                    <!-- Projects Widget -->
                    <div class="card stat-card">
                        <h4>Active Projects</h4>
                        <div class="stat-value">
                            <?php echo mysqli_num_rows($projects_result); ?>
                        </div>
                        <p class="stat-label">Available to work on</p>
                    </div>
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
                                    mysqli_data_seek($projects_result, 0); // Reset pointer
                                    while ($proj = mysqli_fetch_assoc($projects_result)): ?>
                                        <option value="<?php echo $proj['id']; ?>">
                                            <?php echo htmlspecialchars($proj['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
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