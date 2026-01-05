<?php
// admin_user_review.php
session_start();
require('includes/db_connect.php');
require('includes/auth_session.php');

check_login();
check_admin();

$user_id = $_GET['user_id'];
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Fetch User Name
$user_sql = "SELECT full_name FROM users WHERE id = '$user_id'";
$user_result = mysqli_query($conn, $user_sql);
$user_data = mysqli_fetch_assoc($user_result);
$full_name = $user_data['full_name'];

// Handle Manual Status Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_date'])) {
    $action_date = $_POST['action_date'];
    $action_type = $_POST['action_type'];
    $admin_comment = mysqli_real_escape_string($conn, $_POST['admin_comment']); // e.g. "Excuse", "Mark Leave"

    // Default reason if empty
    if (empty($admin_comment)) {
        $admin_comment = "Manual Review Adjustment";
    }

    $request_type = '';
    $status = 'Approved';

    if ($action_type == 'grant_permission') {
        $request_type = 'Time Permission';
    } elseif ($action_type == 'mark_full_leave') {
        $request_type = 'Full Day Leave';
    } elseif ($action_type == 'mark_half_leave') {
        $request_type = 'Half Day';
    }

    if ($request_type) {
        // Check if duplicate for this date/type to avoid spamming
        $check_sql = "SELECT id FROM leave_requests WHERE user_id='$user_id' AND start_date='$action_date' AND type='$request_type'";
        if (mysqli_num_rows(mysqli_query($conn, $check_sql)) == 0) {
            $insert = "INSERT INTO leave_requests (user_id, type, start_date, end_date, reason, status, admin_comment) 
                       VALUES ('$user_id', '$request_type', '$action_date', '$action_date', '$admin_comment', '$status', '$admin_comment')";
            if (mysqli_query($conn, $insert)) {
                $message = "Status updated successfully for " . date('M d', strtotime($action_date));
            } else {
                $error = "Error updating status: " . mysqli_error($conn);
            }
        } else {
            $error = "A similar request already exists for this date.";
        }
    }
}

// Fetch Attendance for Range
$sql = "SELECT * FROM attendance WHERE user_id = '$user_id' AND date BETWEEN '$start_date' AND '$end_date' ORDER BY date ASC";
$result = mysqli_query($conn, $sql);
$attendance_data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $attendance_data[$row['date']][] = $row;
}

// Fetch Approved Leaves/Permissions for Range
$leaves_sql = "SELECT * FROM leave_requests WHERE user_id = '$user_id' AND status = 'Approved' 
               AND ((start_date BETWEEN '$start_date' AND '$end_date') OR (end_date BETWEEN '$start_date' AND '$end_date'))";
$leaves_result = mysqli_query($conn, $leaves_sql);
$leaves_data = [];
while ($row = mysqli_fetch_assoc($leaves_result)) {
    // Expand date ranges to array keys
    $period = new DatePeriod(
        new DateTime($row['start_date']),
        new DateInterval('P1D'),
        (new DateTime($row['end_date']))->modify('+1 day')
    );
    foreach ($period as $dt) {
        $date_key = $dt->format("Y-m-d");
        $leaves_data[$date_key][] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review: <?php echo $full_name; ?> - SmartFusion Team</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .status-undertime {
            color: #DC2626;
            font-weight: bold;
        }

        .status-ok {
            color: #16A34A;
        }

        .status-excused {
            color: #F59E0B;
            font-weight: bold;
        }

        /* Amber for excused */
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
                <div class="flex items-center gap-4">
                    <a href="monthly_evaluation.php" class="btn btn-primary" style="padding: 0.5rem;">&larr; Back</a>
                    <h3>Review: <?php echo $full_name; ?></h3>
                </div>
            </header>

            <div class="page-content">
                <?php if (isset($message)): ?>
                    <div
                        style="background-color: #DCFCE7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div
                        style="background-color: #FEE2E2; color: #991B1B; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>First Login</th>
                                    <th>Last Logout</th>
                                    <th>Total Hours</th>
                                    <th>Notes/Leaves</th>
                                    <th>Status</th>
                                    <th style="min-width: 300px;">Action / Comment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $current = strtotime($start_date);
                                $last = strtotime($end_date);

                                while ($current <= $last) {
                                    $date_str = date('Y-m-d', $current);
                                    $day_attendance = $attendance_data[$date_str] ?? null;
                                    $day_leaves = $leaves_data[$date_str] ?? null;

                                    // Calculations
                                    $first_login = '-';
                                    $last_logout = '-';
                                    $total_hours = 0;
                                    $status_class = '';
                                    $status_text = 'Absent';

                                    if ($day_attendance) {
                                        // 1. Find the earliest login time for the day
                                        $logins = array_column($day_attendance, 'login_time');
                                        $first_login = min($logins);

                                        // 2. Find the latest logout time (handling potential multiple sessions)
                                        $max_l = null;
                                        foreach ($day_attendance as $da) {
                                            if ($da['logout_time']) {
                                                if (!$max_l || strtotime($da['logout_time']) > strtotime($max_l)) {
                                                    $max_l = $da['logout_time'];
                                                }
                                            }
                                        }
                                        $last_logout = $max_l;

                                        // 3. Calculate Total Duration
                                        // If user is currently online (no logout), use current time for calculation or ignore.
                                        $f_time = strtotime($first_login);
                                        $l_time = $last_logout ? strtotime($last_logout) : time();

                                        $duration = ($l_time - $f_time) / 3600;
                                        $total_hours = number_format($duration, 2);

                                        // 4. Determine Present/Undertime Status based on 8.5 hours threshold
                                        if ($duration >= 8.5) {
                                            $status_text = "Present";
                                            $status_class = "status-ok";
                                        } else {
                                            $status_text = "Undertime";
                                            $status_class = "status-undertime";
                                        }
                                    } else {
                                        $status_text = "Absent";
                                        $status_class = "";
                                    }

                                    // CHECK LEAVES / PERMISSIONS
                                    $leaf_note = "";
                                    $is_excused = false;
                                    if ($day_leaves) {
                                        foreach ($day_leaves as $leaf) {
                                            $leaf_note .= "<div class='text-xs' style='margin-bottom:2px;'><span class='badge badge-success'>" . $leaf['type'] . "</span> <span class='text-muted'>(" . $leaf['admin_comment'] . ")</span></div>";
                                            if ($leaf['type'] == 'Time Permission' && $status_text == 'Undertime') {
                                                $status_text = "Permission Granted";
                                                $status_class = "status-excused";
                                                $is_excused = true;
                                            }
                                            if (($leaf['type'] == 'Full Day Leave' || $leaf['type'] == 'Half Day') && $status_text == 'Absent') {
                                                $status_text = "On Leave";
                                                $status_class = "status-excused";
                                                $is_excused = true;
                                            }
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td><strong><?php echo date('M d', $current); ?></strong></td>
                                        <td><?php echo $first_login != '-' ? date('H:i', strtotime($first_login)) : '-'; ?>
                                        </td>
                                        <td><?php echo $last_logout != '-' && $last_logout ? date('H:i', strtotime($last_logout)) : '-'; ?>
                                        </td>
                                        <td><?php echo $total_hours > 0 ? $total_hours . 'h' : '-'; ?></td>
                                        <td><?php echo $leaf_note; ?></td>
                                        <td class="<?php echo $status_class; ?>"><?php echo $status_text; ?></td>
                                        <td>
                                            <form method="post" class="flex gap-2 items-center">
                                                <input type="hidden" name="action_date" value="<?php echo $date_str; ?>">
                                                <input type="text" name="admin_comment" placeholder="Reason..."
                                                    class="form-control"
                                                    style="width: 120px; font-size: 0.8rem; padding: 0.25rem;">

                                                <select name="action_type" class="form-control"
                                                    style="width: auto; font-size: 0.8rem; padding: 0.25rem;">
                                                    <option value="grant_permission">Excuse (Permission)</option>
                                                    <option value="mark_full_leave">Mark Full Leave</option>
                                                </select>

                                                <button type="submit" class="btn btn-primary text-xs"
                                                    style="padding: 0.25rem 0.5rem;">Update</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php
                                    $current = strtotime('+1 day', $current);
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>

</html>