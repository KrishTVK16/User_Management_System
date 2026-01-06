<?php
// manage_projects.php
session_start();
require('includes/db_connect.php');
require('includes/auth_session.php');

check_login();
check_admin();

// Handle Form Submissions
$message = "";
$error = "";

// 1. Add Project
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_project') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $status = $_POST['status'];

    $sql = "INSERT INTO projects (name, description, status) VALUES ('$name', '$description', '$status')";
    if (mysqli_query($conn, $sql)) {
        $message = "Project created successfully!";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

// 2. Assign User
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'assign_user') {
    $user_id = $_POST['user_id'];
    $project_id = $_POST['project_id'];

    // Check if already assigned
    $check = "SELECT * FROM project_assignments WHERE user_id='$user_id' AND project_id='$project_id'";
    if (mysqli_num_rows(mysqli_query($conn, $check)) == 0) {
        $sql = "INSERT INTO project_assignments (user_id, project_id) VALUES ('$user_id', '$project_id')";
        if (mysqli_query($conn, $sql)) {
            $message = "User assigned successfully!";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    } else {
        $error = "User is already assigned to this project.";
    }
}

// Fetch Data
$projects = mysqli_query($conn, "SELECT * FROM projects ORDER BY created_at DESC");
$users = mysqli_query($conn, "SELECT id, full_name FROM users WHERE role='employee' AND is_active=1");
$assignments = mysqli_query($conn, "SELECT pa.id, u.full_name, p.name as project_name, pa.assigned_at 
                                    FROM project_assignments pa 
                                    JOIN users u ON pa.user_id = u.id 
                                    JOIN projects p ON pa.project_id = p.id 
                                    ORDER BY pa.assigned_at DESC");

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Projects - SmartFusion Team</title>
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
                <a href="manage_projects.php" class="nav-item active">Projects</a>
                <a href="manage_employees.php" class="nav-item">Employees</a>
                <a href="reports.php" class="nav-item">Reports</a>
                <a href="manage_leaves.php" class="nav-item">Leaves & Permissions</a>
                <a href="monthly_evaluation.php" class="nav-item">Evaluations</a>
            </nav>
            <div class="sidebar-header" style="border-top: 1px solid #334155;">
                <a href="logout.php" class="nav-item" style="color: #EF4444;">Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <button class="menu-toggle">☰</button>
                <h3>Project Management</h3>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-semibold">
                        <?php echo $_SESSION['full_name']; ?>
                    </span>
                </div>
            </header>

            <div class="page-content">

                <?php if ($message): ?>
                    <div
                        style="background-color: #DCFCE7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div
                        style="background-color: #FEE2E2; color: #991B1B; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="grid-3" style="grid-template-columns: 1fr 1fr;">

                    <!-- Create Project -->
                    <div class="card">
                        <h4 class="mb-4">Create New Project</h4>
                        <form method="post">
                            <input type="hidden" name="action" value="add_project">
                            <div class="form-group">
                                <label class="form-label">Project Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option>Active</option>
                                    <option>On Hold</option>
                                    <option>Completed</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Create Project</button>
                        </form>
                    </div>

                    <!-- Assign Employee -->
                    <div class="card">
                        <h4 class="mb-4">Assign Employee to Project</h4>
                        <form method="post">
                            <input type="hidden" name="action" value="assign_user">
                            <div class="form-group">
                                <label class="form-label">Select Project</label>
                                <select name="project_id" class="form-control" required>
                                    <?php
                                    mysqli_data_seek($projects, 0);
                                    while ($p = mysqli_fetch_assoc($projects)): ?>
                                        <option value="<?php echo $p['id']; ?>">
                                            <?php echo htmlspecialchars($p['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Select Employee</label>
                                <select name="user_id" class="form-control" required>
                                    <?php while ($u = mysqli_fetch_assoc($users)): ?>
                                        <option value="<?php echo $u['id']; ?>">
                                            <?php echo htmlspecialchars($u['full_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-outline">Assign User</button>
                        </form>
                    </div>

                </div>

                <div class="card mt-4">
                    <h4 class="mb-4">Project Overview</h4>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>Status</th>
                                    <th>Description</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                mysqli_data_seek($projects, 0);
                                while ($row = mysqli_fetch_assoc($projects)): ?>
                                    <tr>
                                        <td><strong>
                                                <?php echo htmlspecialchars($row['name']); ?>
                                            </strong></td>
                                        <td><span class="badge" style="background:#E2E8F0;">
                                                <?php echo $row['status']; ?>
                                            </span></td>
                                        <td>
                                            <?php echo htmlspecialchars($row['description']); ?>
                                        </td>
                                        <td>
                                            <?php echo date('Y-m-d', strtotime($row['created_at'])); ?>
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