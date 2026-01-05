<?php
// my_projects.php
session_start();
require('includes/db_connect.php');
require('includes/auth_session.php');

check_login();

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Get Assigned Projects
$sql = "SELECT p.*, pa.assigned_at 
        FROM projects p 
        JOIN project_assignments pa ON p.id = pa.project_id 
        WHERE pa.user_id = '$user_id' 
        ORDER BY p.created_at DESC";
$result = mysqli_query($conn, $sql);

// Fallback logic for demo if no assignments
if (mysqli_num_rows($result) == 0) {
    // Show all active projects for demo feel so it isn't empty
    $sql = "SELECT *, '2023-01-01' as assigned_at FROM projects WHERE status='Active'";
    $result = mysqli_query($conn, $sql);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Projects - SmartFusion Team</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo" style="color: white; font-size: 1.5rem;">SmartFusion Team</div>
            </div>
            <nav class="sidebar-nav">
                <a href="employee_dashboard.php" class="nav-item">Dashboard</a>
                <a href="my_projects.php" class="nav-item active">My Projects</a>
                <a href="my_leaves.php" class="nav-item">Leaves & Permissions</a>
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
                        <div class="card">
                            <div class="flex justify-between items-start mb-4">
                                <h4>
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </h4>
                                <span class="badge badge-success">
                                    <?php echo $row['status']; ?>
                                </span>
                            </div>
                            <p class="text-muted mb-4">
                                <?php echo htmlspecialchars($row['description']); ?>
                            </p>
                            <div class="text-sm text-muted">
                                Assigned:
                                <?php echo date('M d, Y', strtotime($row['assigned_at'])); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </main>
    </div>
</body>

</html>