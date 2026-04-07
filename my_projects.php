<?php
// my_projects.php
session_start();
require('includes/db_connect.php');
require('includes/auth_session.php');

check_login();

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Get My Projects (Hierarchical logic from dashboard)
$sub_role = $_SESSION['sub_role'] ?? 'None';

if ($sub_role == 'Tester') {
    $sql = "SELECT p.*, m.name as master_name 
            FROM projects p 
            LEFT JOIN projects m ON p.parent_id = m.id
            WHERE p.tester_id = '$user_id' AND p.status IN ('Testing', 'Corrected', 'Finalized', 'Client Submitted') 
            ORDER BY m.name ASC, p.name ASC";
} else {
    $sql = "SELECT p.*, m.name as master_name 
            FROM projects p 
            LEFT JOIN projects m ON p.parent_id = m.id
            WHERE p.developer_id = '$user_id' AND p.status IN ('Assigned', 'Development Initialized', 'Correction Required', 'Corrected', 'Development Completed') 
            ORDER BY m.name ASC, p.name ASC";
}
$result = mysqli_query($conn, $sql);

// Removed outdated fallback logic as we now use direct assignments in the projects table.

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Projects - SmartFusion Team</title>
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
                <h3>My Projects</h3>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-semibold">
                        <?php echo $full_name; ?>
                    </span>
                </div>
            </header>

            <div class="page-content">
                <div class="grid-3" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <div class="mb-4">
                                <span class="text-xs text-primary font-bold">
                                    <?php echo htmlspecialchars($row['master_name'] ?: 'Standalone Project'); ?>
                                </span>
                                <h4 class="mt-1">
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </h4>
                            </div>
                            
                            <div class="flex justify-between items-center mb-4">
                                <span class="badge badge-success" style="background: var(--bg-body); border: 1px solid var(--border-color); color: var(--primary-color);">
                                    <?php echo $row['status']; ?>
                                </span>
                                <?php if($row['is_delayed']): ?>
                                    <span class="text-xs text-danger font-bold">DELAYED</span>
                                <?php endif; ?>
                            </div>

                            <p class="text-muted text-sm mb-4" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo htmlspecialchars($row['description']); ?>
                            </p>

                            <div class="flex justify-between items-center pt-4 border-t border-gray-100">
                                <div class="text-xs text-muted">
                                    Assigned At:<br>
                                    <span class="font-semibold"><?php echo $row['assigned_at'] ? date('M d, Y', strtotime($row['assigned_at'])) : 'Pending'; ?></span>
                                </div>
                                <a href="project_view.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;">View Details</a>
                            </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </main>
    </div>
</body>

</html>