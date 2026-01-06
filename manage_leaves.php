<?php
// manage_leaves.php
session_start();
require('includes/db_connect.php');
require('includes/auth_session.php');

check_login();
check_admin();

// Fetch Pending Requests
// Shows only requests that haven't been acted upon yet
$pending_sql = "SELECT l.*, u.full_name FROM leave_requests l JOIN users u ON l.user_id = u.id WHERE l.status = 'Pending' ORDER BY l.created_at ASC";
$pending_result = mysqli_query($conn, $pending_sql);

// Fetch History
// Shows past 50 processed requests for reference
$history_sql = "SELECT l.*, u.full_name FROM leave_requests l JOIN users u ON l.user_id = u.id WHERE l.status != 'Pending' ORDER BY l.created_at DESC LIMIT 50";
$history_result = mysqli_query($conn, $history_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Leaves - SmartFusion Team</title>
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
                <a href="reports.php" class="nav-item">Reports</a>
                <a href="manage_leaves.php" class="nav-item active">Leaves & Permissions</a>
                <a href="monthly_evaluation.php" class="nav-item">Evaluations</a>
            </nav>
            <div class="sidebar-header" style="border-top: 1px solid #334155;">
                <a href="logout.php" class="nav-item" style="color: #EF4444;">Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <button class="menu-toggle">☰</button>
                <h3>Manage Leaves & Permissions</h3>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-semibold"><?php echo $_SESSION['full_name']; ?></span>
                </div>
            </header>

            <div class="page-content">
                
                <?php if(isset($_SESSION['message'])): ?>
                    <div style="background-color: #DCFCE7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                        <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <h4 class="mb-4">Pending Requests</h4>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Type</th>
                                    <th>Dates</th>
                                    <th>Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($pending_result) > 0): ?>
                                    <?php while($row = mysqli_fetch_assoc($pending_result)): ?>
                                        <tr>
                                            <td><strong><?php echo $row['full_name']; ?></strong></td>
                                            <td><?php echo $row['type']; ?></td>
                                            <td>
                                                <?php 
                                                    echo date('M d', strtotime($row['start_date']));
                                                    if($row['end_date'] != $row['start_date']) {
                                                        echo ' - ' . date('M d', strtotime($row['end_date']));
                                                    }
                                                ?>
                                            </td>
                                            <td style="max-width: 300px;">
                                                <?php echo htmlspecialchars($row['reason']); ?>
                                            </td>
                                            <td>
                                                <div class="flex gap-2">
                                                    <form action="actions/update_leave_status.php" method="post" class="flex gap-2">
                                                        <input type="hidden" name="leave_id" value="<?php echo $row['id']; ?>">
                                                        <input type="hidden" name="admin_comment" value="Approved">
                                                        <button type="submit" name="status" value="Approved" class="btn btn-success text-xs">Approve</button>
                                                        <button type="submit" name="status" value="Rejected" class="btn btn-danger text-xs">Reject</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center text-muted">No pending requests.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <h4 class="mb-4">Request History</h4>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Dates</th>
                                    <th>Admin Comment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($history_result)): ?>
                                    <tr>
                                        <td><?php echo $row['full_name']; ?></td>
                                        <td><?php echo $row['type']; ?></td>
                                        <td>
                                            <?php if($row['status'] == 'Approved'): ?>
                                                <span class="badge badge-success">Approved</span>
                                            <?php elseif($row['status'] == 'Rejected'): ?>
                                                <span class="badge badge-danger">Rejected</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                                echo date('M d', strtotime($row['start_date']));
                                                if($row['end_date'] != $row['start_date']) {
                                                    echo ' - ' . date('M d', strtotime($row['end_date']));
                                                }
                                            ?>
                                        </td>
                                        <td class="text-sm text-muted">
                                            <?php echo $row['admin_comment'] ?: '-'; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>
