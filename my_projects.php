<?php
// my_projects.php
session_start();
require('includes/db_connect.php');
require('includes/auth_session.php');

check_login();

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// NEW Unified Query: Fetches all projects where the user has an ACTIVE role
// We show projects in statuses relevant to either Developer or Tester
$sql = "SELECT p.*, m.name as master_name 
        FROM projects p 
        LEFT JOIN projects m ON p.parent_id = m.id
        WHERE 
            (p.developer_id = '$user_id' AND p.status IN ('Assigned', 'Development Initialized', 'Correction Required'))
            OR
            (p.tester_id = '$user_id' AND p.status IN ('Testing', 'Corrected', 'Development Completed', 'Finalized'))
        ORDER BY 
            (CASE 
                WHEN p.status = 'Testing' OR p.status = 'Corrected' THEN 1 
                WHEN p.status = 'Correction Required' THEN 1 
                ELSE 2 
            END), 
            p.id DESC";

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Projects - SmartFusion Team</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
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
                <div class="flex items-center gap-2">
                    <span class="text-muted">Assignments</span>
                    <span class="text-muted">/</span>
                    <span class="font-bold">Active Projects</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-semibold"><?php echo $full_name; ?></span>
                </div>
            </header>

            <div class="page-content">
                <div class="v-stack">
                    <div class="flex justify-between items-end mb-2">
                        <div>
                            <h2 class="hero-title" style="font-size: 1.5rem;">Work Queue</h2>
                            <p class="text-muted text-sm">Projects that require your immediate attention</p>
                        </div>
                    </div>

                    <div class="project-list">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): 
                                // Determine the user's role for this specific project
                                $role_label = ($row['tester_id'] == $user_id) ? 'TESTER' : 'DEVELOPER';
                                $role_color = ($row['tester_id'] == $user_id) ? '#3B82F6' : '#D4AF37';
                            ?>
                                <a href="project_view.php?id=<?php echo $row['id']; ?>" class="project-row-card">
                                    <div class="flex items-center gap-6" style="flex: 1;">
                                        <div class="status-ring" style="width: 48px; height: 48px; border-radius: 12px; font-size: 1.25rem; border-color: <?php echo $role_color; ?>33; background: <?php echo $role_color; ?>0d;">
                                            <?php echo ($role_label == 'TESTER') ? '🔍' : '🚀'; ?>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="hero-label" style="font-size: 0.6rem; opacity: 0.8;"><?php echo htmlspecialchars($row['master_name'] ?: 'Standalone Project'); ?></span>
                                                <span class="badge" style="font-size: 0.5rem; background: <?php echo $role_color; ?>; color: #14202E; border: none; padding: 0.1rem 0.4rem;"><?php echo $role_label; ?></span>
                                            </div>
                                            <h4 class="text-white mt-1" style="font-size: 1.1rem;"><?php echo htmlspecialchars($row['name']); ?></h4>
                                            <p class="text-muted text-xs mt-1" style="max-width: 450px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?php echo htmlspecialchars($row['description']); ?>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-8">
                                        <div class="meta-item text-right">
                                            <span class="hero-label" style="font-size: 0.6rem; opacity: 0.7;">Due Date</span>
                                            <span class="text-xs font-bold"><?php echo $row['assigned_at'] ? date('M d', strtotime($row['assigned_at'])) : '---'; ?></span>
                                        </div>
                                        
                                        <div class="meta-item text-center" style="min-width: 130px;">
                                            <span class="hero-label" style="font-size: 0.6rem; opacity: 0.7;">Status</span>
                                            <span class="status-ring" style="font-size: 0.65rem; padding: 0.35rem 0.85rem; border-width: 1px; width: auto; height: auto;">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </div>

                                        <div class="btn btn-outline" style="padding: 0.6rem 1.2rem; font-size: 0.75rem; border-radius: 2rem;">
                                            View Project
                                        </div>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="premium-card text-center py-20">
                                <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">🌴</span>
                                <h3 class="text-white">All caught up!</h3>
                                <p class="text-muted">You don't have any active tasks in your queue right now.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>

</html>