<?php
// my_leaves.php
session_start();
require('includes/db_connect.php');
require('includes/auth_session.php');

check_login();

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Fetch My Leaves
$sql = "SELECT * FROM leave_requests WHERE user_id = '$user_id' ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Leaves & Permissions - SmartFusion Team</title>
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
                <a href="my_leaves.php" class="nav-item active">Leaves & Permissions</a>
                <a href="my_history.php" class="nav-item">History</a>
                <a href="profile.php" class="nav-item">Profile</a>
            </nav>
            <div class="sidebar-header" style="border-top: 1px solid #334155;">
                <a href="logout.php" class="nav-item" style="color: #EF4444;">Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <button class="menu-toggle">☰</button>
                <h3>My Leaves & Permissions</h3>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-semibold"><?php echo $full_name; ?></span>
                </div>
            </header>

            <div class="page-content">

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

                <div class="grid-3" style="grid-template-columns: 1fr 1.5fr;">

                    <!-- Apply Form -->
                    <div class="card">
                        <h4 class="mb-4">Apply for Leave / Permission</h4>
                        <form action="actions/submit_leave.php" method="post">
                            <div class="form-group">
                                <label class="form-label">Request Type</label>
                                <select name="type" class="form-control" required onchange="toggleEndDate(this.value)">
                                    <option value="Time Permission">Time Permission (Short Duration)</option>
                                    <option value="Full Day Leave">Full Day Leave</option>
                                    <option value="Half Day">Half Day</option>
                                </select>
                            </div>

                            <div class="flex gap-4">
                                <div class="form-group" style="flex:1;">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" name="start_date" class="form-control" required>
                                </div>
                                <div class="form-group" id="end_date_group" style="flex:1; display:none;">
                                    <label class="form-label">End Date</label>
                                    <input type="date" name="end_date" class="form-control">
                                    <small class="text-muted">Leave empty for single day.</small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Reason</label>
                                <textarea name="reason" class="form-control" rows="3"
                                    placeholder="Examples: Doctor Appointment, Personal Emergency, etc."
                                    required></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Submit Request</button>
                        </form>
                    </div>

                    <!-- History -->
                    <div class="card">
                        <h4 class="mb-4">Request History</h4>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date Submitted</th>
                                        <th>Type</th>
                                        <th>Dates Requested</th>
                                        <th>Status</th>
                                        <th>Admin Comment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($result) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td><?php echo date('M d', strtotime($row['created_at'])); ?></td>
                                                <td><?php echo $row['type']; ?></td>
                                                <td>
                                                    <?php
                                                    echo date('M d', strtotime($row['start_date']));
                                                    if ($row['end_date'] != $row['start_date']) {
                                                        echo ' - ' . date('M d', strtotime($row['end_date']));
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if ($row['status'] == 'Approved'): ?>
                                                        <span class="badge badge-success">Approved</span>
                                                    <?php elseif ($row['status'] == 'Rejected'): ?>
                                                        <span class="badge badge-danger">Rejected</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-sm text-muted">
                                                    <?php echo $row['admin_comment'] ?: '-'; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No requests found.</td>
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

    <script>
        function toggleEndDate(type) {
            const endDateGroup = document.getElementById('end_date_group');
            if (type === 'Full Day Leave') {
                endDateGroup.style.display = 'block';
            } else {
                endDateGroup.style.display = 'none';
            }
        }
    </script>
</body>

</html>