<?php
// manage_projects.php
session_start();
require('includes/db_connect.php');
require('includes/auth_session.php');

require('includes/project_functions.php');

check_login();
check_admin();

$user_id_session = $_SESSION['user_id'];
$user_role_session = $_SESSION['role'];

// Handle Form Submissions
$message = "";
$error = "";

// 1. Add/Assign Project
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_project') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $client_name = mysqli_real_escape_string($conn, $_POST['client_name']);
    $project_link = mysqli_real_escape_string($conn, $_POST['project_link']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $developer_id = $_POST['developer_id'];
    $tester_id = $_POST['tester_id'];
    $status = 'Assigned';
    $now = date('Y-m-d H:i:s');

    $sql = "INSERT INTO projects (name, client_name, project_link, description, developer_id, tester_id, status, assigned_at) 
            VALUES ('$name', '$client_name', '$project_link', '$description', '$developer_id', '$tester_id', '$status', '$now')";
    
    if (mysqli_query($conn, $sql)) {
        $project_id = mysqli_insert_id($conn);
        log_project_history($conn, $project_id, $user_id_session, "Assigned project", "", "Assigned");
        
        // Notifications
        create_notification($conn, $developer_id, "New project assigned: $name", 'info');
        create_notification($conn, $tester_id, "New project assigned for testing: $name", 'info');
        
        $message = "Project created and assigned successfully!";
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
$projects = mysqli_query($conn, "SELECT p.*, d.full_name as dev_name, t.full_name as tester_name 
                                FROM projects p 
                                LEFT JOIN users d ON p.developer_id = d.id 
                                LEFT JOIN users t ON p.tester_id = t.id 
                                ORDER BY p.created_at DESC");

$developers = mysqli_query($conn, "SELECT id, full_name FROM users WHERE (sub_role='Developer' OR sub_role='Full Stack' OR sub_role='None') AND is_active=1");
$testers = mysqli_query($conn, "SELECT id, full_name FROM users WHERE (sub_role='Tester' OR sub_role='Full Stack' OR sub_role='None') AND is_active=1");

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
            <div class="sidebar-header" style="border-top: 2px solid var(--border-color);">
                <a href="logout.php" class="nav-item" style="color: #EF4444; font-weight: 700;">Logout</a>
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
                        style="background-color: #1B2B3D; color: var(--primary-color); border: 2px solid var(--primary-color); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 700;">
                        <strong>SUCCESS:</strong> <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div
                        style="background-color: #2A1A1A; color: #EF4444; border: 2px solid #EF4444; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 700;">
                        <strong>ERROR:</strong> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="grid-1">

                    <!-- Create & Assign Project -->
                    <div class="card">
                        <h4 class="mb-4">Create & Assign New Project</h4>
                        <form method="post" class="grid-2">
                            <input type="hidden" name="action" value="add_project">
                            <div class="form-group">
                                <label class="form-label">Project Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Client Name</label>
                                <input type="text" name="client_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Project Link (if any)</label>
                                <input type="url" name="project_link" class="form-control" placeholder="https://...">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="1"></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Assign Developer</label>
                                <select name="developer_id" class="form-control" required>
                                    <option value="">Select Developer</option>
                                    <?php while ($d = mysqli_fetch_assoc($developers)): ?>
                                        <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['full_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Assign Tester</label>
                                <select name="tester_id" class="form-control" required>
                                    <option value="">Select Tester</option>
                                    <?php while ($t = mysqli_fetch_assoc($testers)): ?>
                                        <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['full_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div style="grid-column: span 2;">
                                <button type="submit" class="btn btn-primary">Create & Assign Project</button>
                            </div>
                        </form>
                    </div>

                </div>

                <div class="card mt-4">
                    <h4 class="mb-4">Project Lifecycle Overview</h4>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Project / Client</th>
                                    <th>Developer</th>
                                    <th>Tester</th>
                                    <th>Current Status</th>
                                    <th>Timeline</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                mysqli_data_seek($projects, 0);
                                while ($row = mysqli_fetch_assoc($projects)): 
                                    $status_color = "#E2E8F0";
                                    if ($row['status'] == 'Delayed') $status_color = "#FEE2E2";
                                    if ($row['status'] == 'Finalized') $status_color = "#DCFCE7";
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                                            <span class="text-xs text-muted"><?php echo htmlspecialchars($row['client_name']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['dev_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['tester_name']); ?></td>
                                        <td>
                                            <span class="badge" style="background:<?php echo $status_color; ?>;">
                                                <?php echo $row['status']; ?>
                                                <?php if($row['is_delayed']): ?>
                                                    <span style="color:red;">(Delayed)</span>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-xs">Assigned: <?php echo date('M d', strtotime($row['assigned_at'])); ?></span><br>
                                            <?php if($row['started_at']): ?>
                                                <span class="text-xs">Started: <?php echo date('M d', strtotime($row['started_at'])); ?></span>
                                            <?php endif; ?>
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