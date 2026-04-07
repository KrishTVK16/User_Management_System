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

// 1. Add/Assign Project (Modified to handle parent_id)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_project') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : 'NULL';
    $project_type = mysqli_real_escape_string($conn, $_POST['project_type'] ?? 'Static HTML');
    $client_name = mysqli_real_escape_string($conn, $_POST['client_name']);
    $project_link = mysqli_real_escape_string($conn, $_POST['project_link']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $requirements = mysqli_real_escape_string($conn, $_POST['requirements'] ?? '');
    $developer_id = !empty($_POST['developer_id']) ? $_POST['developer_id'] : 'NULL';
    $tester_id = !empty($_POST['tester_id']) ? $_POST['tester_id'] : 'NULL';
    $status = 'Assigned';
    $now = date('Y-m-d H:i:s');

    $sql = "INSERT INTO projects (parent_id, name, client_name, project_link, description, requirements, project_type, developer_id, tester_id, status, assigned_at) 
            VALUES ($parent_id, '$name', '$client_name', '$project_link', '$description', '$requirements', '$project_type', $developer_id, $tester_id, '$status', '$now')";
    
    if (mysqli_query($conn, $sql)) {
        $project_id = mysqli_insert_id($conn);
        log_project_history($conn, $project_id, $user_id_session, "Created project", "", "Assigned");
        
        if ($developer_id !== 'NULL') create_notification($conn, $developer_id, "New project assigned: $name", 'info');
        if ($tester_id !== 'NULL') create_notification($conn, $tester_id, "New project assigned for testing: $name", 'info');
        
        $message = "Project created successfully!";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

// 2. Bulk Assign
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'bulk_assign') {
    $project_ids = $_POST['selected_projects'] ?? [];
    $dev_id = !empty($_POST['bulk_dev_id']) ? $_POST['bulk_dev_id'] : null;
    $test_id = !empty($_POST['bulk_test_id']) ? $_POST['bulk_test_id'] : null;

    if (empty($project_ids)) {
        $error = "No projects selected for assignment.";
    } else {
        $success_count = 0;
        foreach ($project_ids as $pid) {
            $pid = (int)$pid;
            $updates = [];
            if ($dev_id) $updates[] = "developer_id = $dev_id";
            if ($test_id) $updates[] = "tester_id = $test_id";
            
            if (!empty($updates)) {
                $sql = "UPDATE projects SET " . implode(", ", $updates) . ", assigned_at = NOW() WHERE id = $pid";
                if (mysqli_query($conn, $sql)) {
                    $success_count++;
                }
            }
        }
        $message = "Successfully assigned $success_count projects.";
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

// Fetch Master Projects (Include created_at to distinguish duplicates)
$master_projects_query = "SELECT id, name, created_at FROM projects WHERE parent_id IS NULL ORDER BY created_at DESC";
$master_projects = mysqli_query($conn, $master_projects_query);

// Fetch All Projects with Parent/Master details
$projects_query = "SELECT p.*, d.full_name as dev_name, t.full_name as tester_name, m.name as master_name 
                   FROM projects p 
                   LEFT JOIN users d ON p.developer_id = d.id 
                   LEFT JOIN users t ON p.tester_id = t.id 
                   LEFT JOIN projects m ON p.parent_id = m.id 
                   ORDER BY COALESCE(p.parent_id, p.id) DESC, p.parent_id IS NOT NULL, p.created_at DESC";
$projects_result = mysqli_query($conn, $projects_query);

// Apply Super Admin exclusion for Developer and Tester lists
$visibility_filter = get_user_visibility_clause($user_role_session);


$developers = mysqli_query($conn, "SELECT id, full_name FROM users WHERE (sub_role='Developer' OR sub_role='Full Stack' OR sub_role='None') AND is_active=1 $visibility_filter");
$testers = mysqli_query($conn, "SELECT id, full_name FROM users WHERE (sub_role='Tester' OR sub_role='Full Stack' OR sub_role='None') AND is_active=1 $visibility_filter");

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Projects - SmartFusion Team</title>
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
                <a href="admin_dashboard.php" class="nav-item">Dashboard</a>
                <a href="manage_projects.php" class="nav-item active">Projects</a>
                <a href="manage_employees.php" class="nav-item">Employees</a>
                <a href="reports.php" class="nav-item">Reports</a>
                <a href="manage_leaves.php" class="nav-item">Leaves & Permissions</a>
                <a href="monthly_evaluation.php" class="nav-item">Evaluations</a>
                <a href="admin_cleanup.php" class="nav-item" style="color: #EF4444; font-weight: 700; border-left: 0; border-top: 1px solid var(--border-color); padding-top: 1rem; margin-top: 1rem;">System Maintenance</a>
                <a href="logout.php" class="nav-item" style="color: #EF4444; border-left: 0; margin-top: 1rem; border-top: 2px solid var(--border-color); padding-top: 1.5rem;">Logout</a>
            </nav>
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

                <div class="tabs-nav">
                    <button class="tab-link active" onclick="openTab(event, 'overview-tab')">Overview</button>
                    <button class="tab-link" onclick="openTab(event, 'quick-add-tab')">Quick Add</button>
                    <button class="tab-link" onclick="openTab(event, 'master-projects-tab')">Master Projects</button>
                    <button class="tab-link" onclick="openTab(event, 'import-tab')">Import Sites</button>
                </div>

                <!-- Tab: Master Projects -->
                <div id="master-projects-tab" class="tab-pane">
                    <div class="grid-2">
                        <!-- Create Master Project -->
                        <div class="card">
                            <h4 class="mb-4">Create Master Project (e.g. March Slot)</h4>
                            <form method="post">
                                <input type="hidden" name="action" value="add_project">
                                <div class="form-group">
                                    <label class="form-label">Project Name</label>
                                    <input type="text" name="name" class="form-control" placeholder="e.g. March 2026 Slot" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Internal Purpose Description</label>
                                    <textarea name="description" class="form-control" rows="1" placeholder="Internal tracking for this month..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-full">Create Master Project</button>
                            </form>
                        </div>
                        <div class="card">
                            <h4>Existing Master Projects</h4>
                            <p class="text-sm text-muted mb-4">You have multiple slots? Check dates below to distinguish them.</p>
                            <div class="table-container" style="max-height: 200px; overflow-y: auto;">
                                <table>
                                    <tbody>
                                        <?php 
                                        mysqli_data_seek($master_projects, 0);
                                        while ($mp = mysqli_fetch_assoc($master_projects)): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($mp['name']); ?></strong></td>
                                                <td class="text-xs text-muted"><?php echo date('M d, Y', strtotime($mp['created_at'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Import -->
                <div id="import-tab" class="tab-pane">
                    <div class="card">
                        <h4 class="mb-4">Import Sites from Excel/CSV</h4>
                        <p class="text-xs text-muted mb-4">Upload a CSV with columns: <strong>WEBSITE NAME, WEBSITE REQUIREMENTS</strong></p>
                        <form action="actions/import_projects.php" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label class="form-label">Select Master Project</label>
                                <select name="parent_id" class="form-control" required>
                                    <option value="">-- Choose Container --</option>
                                    <?php 
                                    mysqli_data_seek($master_projects, 0);
                                    while ($mp = mysqli_fetch_assoc($master_projects)): ?>
                                        <option value="<?php echo $mp['id']; ?>"><?php echo htmlspecialchars($mp['name']); ?> (Created: <?php echo date('M d', strtotime($mp['created_at'])); ?>)</option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Project Type</label>
                                <select name="project_type" class="form-control">
                                    <option value="Static HTML">Static HTML</option>
                                    <option value="Full Website (Backend)">Full Website (Backend)</option>
                                    <option value="Testing Only">Testing Only</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">CSV File</label>
                                <input type="file" name="project_file" class="form-control" accept=".csv" required>
                            </div>
                            <button type="submit" class="btn btn-success w-full">Upload & Bulk Create Sites</button>
                        </form>
                    </div>
                </div>

                <!-- Tab: Quick Add -->
                <div id="quick-add-tab" class="tab-pane">
                    <div class="card">
                        <h4 class="mb-4">Quick Add Single Website/Task</h4>
                        <form method="post" class="grid-3">
                            <input type="hidden" name="action" value="add_project">
                            <div class="form-group">
                                <label class="form-label">Website Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Master Project</label>
                                <select name="parent_id" class="form-control" required>
                                    <option value="">-- Select Master --</option>
                                    <?php 
                                    mysqli_data_seek($master_projects, 0);
                                    while ($mp = mysqli_fetch_assoc($master_projects)): ?>
                                        <option value="<?php echo $mp['id']; ?>"><?php echo htmlspecialchars($mp['name']); ?> (Created: <?php echo date('M d', strtotime($mp['created_at'])); ?>)</option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Website Requirements</label>
                                <textarea name="requirements" class="form-control" rows="1"></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Assign Developer</label>
                                <select name="developer_id" class="form-control">
                                    <option value="">Select Developer</option>
                                    <?php mysqli_data_seek($developers, 0); while ($d = mysqli_fetch_assoc($developers)): ?>
                                        <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['full_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Assign Tester</label>
                                <select name="tester_id" class="form-control">
                                    <option value="">Select Tester</option>
                                    <?php mysqli_data_seek($testers, 0); while ($t = mysqli_fetch_assoc($testers)): ?>
                                        <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['full_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group" style="display:flex; align-items:flex-end;">
                                <button type="submit" class="btn btn-primary w-full">Create Site</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tab: Overview -->
                <div id="overview-tab" class="tab-pane active">

                <div class="card mt-4">
                    <form method="post">
                        <input type="hidden" name="action" value="bulk_assign">
                        <div class="flex justify-between items-center mb-4">
                            <h4>Project Lifecycle Overview</h4>
                            <div class="flex gap-2 items-center" style="background: rgba(255, 255, 255, 0.05); padding: 0.5rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                                <span class="text-xs font-bold">BULK ASSIGN SELECTION TO:</span>
                                <select name="bulk_dev_id" class="form-control" style="width: 150px; padding: 0.4rem;">
                                    <option value="">-- Dev --</option>
                                    <?php mysqli_data_seek($developers, 0); while ($d = mysqli_fetch_assoc($developers)): ?>
                                        <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['full_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <select name="bulk_test_id" class="form-control" style="width: 150px; padding: 0.4rem;">
                                    <option value="">-- Tester --</option>
                                    <?php mysqli_data_seek($testers, 0); while ($t = mysqli_fetch_assoc($testers)): ?>
                                        <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['full_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <button type="submit" class="btn btn-primary" style="padding: 0.4rem 1rem;">Apply</button>
                            </div>
                        </div>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width: 40px;"><input type="checkbox" onclick="toggleSelectAll(this)"></th>
                                        <th>Project / Master</th>
                                        <th>Assignment</th>
                                        <th>Status</th>
                                        <th>Requirements Preview</th>
                                        <th>Timeline</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    mysqli_data_seek($projects_result, 0);
                                    while ($row = mysqli_fetch_assoc($projects_result)): 
                                        $is_master = ($row['parent_id'] == NULL);
                                        $row_style = $is_master ? "background: #1B2B3D; border-left: 4px solid var(--primary-color);" : "";
                                        $status_color = "#E2E8F0";
                                        if ($row['status'] == 'Delayed') $status_color = "#FEE2E2";
                                        if ($row['status'] == 'Finalized') $status_color = "#DCFCE7";
                                    ?>
                                        <tr style="<?php echo $row_style; ?>">
                                            <td>
                                                <?php if(!$is_master): ?>
                                                    <input type="checkbox" name="selected_projects[]" value="<?php echo $row['id']; ?>">
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                                                <span class="text-xs text-muted">
                                                    <?php echo $is_master ? "MASTER PROJECT" : "Master: " . htmlspecialchars($row['master_name']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="text-xs">
                                                    Dev: <strong><?php echo $row['dev_name'] ?? 'Unassigned'; ?></strong><br>
                                                    Tester: <strong><?php echo $row['tester_name'] ?? 'Unassigned'; ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge" style="background:<?php echo $status_color; ?>; color: #14202E;">
                                                    <?php echo $row['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="text-xs text-muted" style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                    <?php echo htmlspecialchars($row['requirements'] ?: ($row['description'] ?: 'No notes')); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-xs">Assigned: <?php echo $row['assigned_at'] ? date('M d', strtotime($row['assigned_at'])) : '---'; ?></span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>

                </div> <!-- End Tab Pane -->

                <script>
                function openTab(evt, tabName) {
                    var i, tabpane, tablinks;
                    tabpane = document.getElementsByClassName("tab-pane");
                    for (i = 0; i < tabpane.length; i++) {
                        tabpane[i].classList.remove("active");
                    }
                    tablinks = document.getElementsByClassName("tab-link");
                    for (i = 0; i < tablinks.length; i++) {
                        tablinks[i].classList.remove("active");
                    }
                    document.getElementById(tabName).classList.add("active");
                    evt.currentTarget.classList.add("active");
                }

                function toggleSelectAll(source) {
                    checkboxes = document.getElementsByName('selected_projects[]');
                    for(var i=0, n=checkboxes.length;i<n;i++) {
                        checkboxes[i].checked = source.checked;
                    }
                }
                </script>

            </div>
        </main>
    </div>
</body>

</html>