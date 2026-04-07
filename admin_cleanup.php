<?php
// admin_cleanup.php
session_start();
require('includes/db_connect.php');
require('includes/auth_session.php');
require('includes/project_functions.php');


check_login();
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin') {
    die("Unauthorized access.");
}

$message = $_SESSION['message'] ?? "";
$error = $_SESSION['error'] ?? "";
unset($_SESSION['message'], $_SESSION['error']);

// 1. Fetch Master Slots (Deduplication Info)
$master_slots_sql = "SELECT m.name, COUNT(m.id) as count, 
                     SUM((SELECT COUNT(*) FROM projects WHERE parent_id = m.id)) as total_subprojects
                     FROM projects m 
                     WHERE m.parent_id IS NULL 
                     GROUP BY m.name 
                     HAVING count > 1";
$duplicates = mysqli_query($conn, $master_slots_sql);

// 2. Fetch Projects (All units)
$units_sql = "SELECT p.*, m.name as master_name FROM projects p 
              LEFT JOIN projects m ON p.parent_id = m.id 
              ORDER BY p.created_at DESC LIMIT 100";
$units_result = mysqli_query($conn, $units_sql);

// 3. Fetch Users (Filter: Only super_admin sees everyone; admin cannot see super_admin)
$visibility = get_user_visibility_clause($_SESSION['role']);
$users_sql = "SELECT * FROM users WHERE 1=1 $visibility AND id != {$_SESSION['user_id']} ORDER BY role ASC";
$users_result = mysqli_query($conn, $users_sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance - SmartFusion Team</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .cleanup-section { margin-bottom: 3rem; }
        .danger-zone { border: 2px solid #EF4444; padding: 2rem; border-radius: var(--radius-md); background: rgba(239, 68, 68, 0.05); }
        .tab-btn { padding: 0.75rem 1.5rem; border: none; background: none; color: var(--text-sub); border-bottom: 2px solid transparent; font-weight: 700; cursor: pointer; transition: all 0.2s; }
        .tab-btn.active { color: var(--primary-color); border-bottom-color: var(--primary-color); }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.3s ease; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="assets/logo.png" alt="SmartFusion" class="logo-img">
                <span class="logo-text">SmartFusion</span>
                <div class="text-sm text-muted" style="margin-left: auto;"><?php echo get_role_label($_SESSION['role']); ?></div>
            </div>

            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="nav-item">Dashboard</a>
                <a href="manage_projects.php" class="nav-item">Projects</a>
                <a href="manage_employees.php" class="nav-item">Employees</a>
                <a href="reports.php" class="nav-item">Reports</a>
                <a href="manage_leaves.php" class="nav-item">Leaves & Permissions</a>
                <a href="monthly_evaluation.php" class="nav-item">Evaluations</a>
                <a href="admin_cleanup.php" class="nav-item active" style="background: #2A1A1A; border-left: 4px solid #EF4444; color: #EF4444;">Maintenance</a>
                <a href="logout.php" class="nav-item" style="color: #EF4444; border-left: 0; margin-top: 1rem; border-top: 2px solid var(--border-color); padding-top: 1.5rem;">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <button class="menu-toggle">☰</button>
                <h3 style="color: #EF4444;">System Maintenance & Cleanup</h3>
                <div class="flex items-center gap-4">
                    <span class="badge badge-danger">ADMIN_ ACCESS</span>
                </div>

            </header>

            <div class="page-content">
                <?php if($message): ?>
                    <div style="background-color: #1B2B3D; color: #10B981; border: 1px solid #10B981; padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem;">
                        <strong>SUCCESS:</strong> <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div style="background-color: #2A1A1A; color: #EF4444; border: 1px solid #EF4444; padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem;">
                        <strong>ERROR:</strong> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="tabs-nav mb-8">
                    <button class="tab-btn active" onclick="switchTab(event, 'slots-tab')">Merge Master Slots</button>
                    <button class="tab-btn" onclick="switchTab(event, 'projects-tab')">Manage All Projects</button>
                    <button class="tab-btn" onclick="switchTab(event, 'users-tab')">Manage All Users</button>
                </div>

                <!-- Tab: Master Slots (Merge) -->
                <div id="slots-tab" class="tab-content active">
                    <div class="card">
                        <div class="flex justify-between items-center mb-4">
                            <h4>Deduplicate Master Project Slots</h4>
                            <form action="actions/system_cleanup.php" method="post" onsubmit="return confirm('DANGER: This will merge all sub-projects and DELETE extra slots. Proceed?')">
                                <input type="hidden" name="action" value="merge_slots">
                                <button type="submit" class="btn btn-primary">Merge All Duplicate Slots Now</button>
                            </form>
                        </div>
                        <p class="text-xs text-muted mb-6">This will look for slots with the same name (e.g. "March Slot") and combine them into one.</p>
                        
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Project Name</th>
                                        <th>Duplicate Entries</th>
                                        <th>Total Websites Affected</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($duplicates) > 0): ?>
                                        <?php while($d = mysqli_fetch_assoc($duplicates)): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($d['name']); ?></strong></td>
                                                <td><span class="badge badge-warning"><?php echo $d['count']; ?> times</span></td>
                                                <td><?php echo $d['total_subprojects']; ?> sub-projects</td>
                                                <td>Requires Merging</td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center text-muted">No duplicate slots found. System is clean.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab: Projects (Delete) -->
                <div id="projects-tab" class="tab-content">
                    <div class="card">
                        <h4>Manage All Sub-Projects & Tasks</h4>
                        <p class="text-xs text-muted mb-6">Recent 100 projects across all categories.</p>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Project Name</th>
                                        <th>Slot / Parent</th>
                                        <th>Created At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($p = mysqli_fetch_assoc($units_result)): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                                            <td><?php echo $p['parent_id'] ? htmlspecialchars($p['master_name']) : '<span class="text-gold">MASTER SLOT</span>'; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td>
                                            <td>
                                                <form action="actions/system_cleanup.php" method="post" onsubmit="return confirm('ERASE DATA: Delete this project permanently?')">
                                                    <input type="hidden" name="action" value="delete_project">
                                                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                    <button type="submit" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;">DELETE</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab: Users (Delete) -->
                <div id="users-tab" class="tab-content">
                    <div class="card danger-zone">
                        <h3 style="color: #EF4444;" class="mb-4">DANGER: USER ACCOUNT REMOVAL</h3>
                        <p class="text-sm text-sub mb-8">Deleting a user will also remove all their attendance records and leave history. This action cannot be undone.</p>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($u = mysqli_fetch_assoc($users_result)): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($u['full_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                                            <td><span class="badge badge-info"><?php echo get_role_label($u['role']); ?></span></td>

                                            <td>
                                                <form action="actions/system_cleanup.php" method="post" onsubmit="return confirm('FINAL WARNING: Delete this user and all their data permanently?')">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                                    <button type="submit" class="btn btn-danger" style="padding: 0.4rem 1rem;">DELETE USER</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        function switchTab(evt, tabId) {
            var i, contents, btns;
            contents = document.getElementsByClassName("tab-content");
            for (i = 0; i < contents.length; i++) {
                contents[i].classList.remove("active");
            }
            btns = document.getElementsByClassName("tab-btn");
            for (i = 0; i < btns.length; i++) {
                btns[i].classList.remove("active");
            }
            document.getElementById(tabId).classList.add("active");
            evt.currentTarget.classList.add("active");
        }

        // Sidebar Toggle for Mobile
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').style.display = 
                document.querySelector('.sidebar').style.display === 'flex' ? 'none' : 'flex';
        });
    </script>
</body>
</html>
