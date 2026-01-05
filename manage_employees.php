<?php
// manage_employees.php
session_start();
require('includes/db_connect.php');
require('includes/auth_session.php');

check_login();
check_admin();

// Handle Add Employee
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_employee') {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password_raw = mysqli_real_escape_string($conn, $_POST['password']);
    
    // Check if email already exists (using email as username based on user request logic)
    $check = "SELECT id FROM users WHERE username='$email' OR email='$email'";
    if (mysqli_num_rows(mysqli_query($conn, $check)) == 0) {
        $password_hash = password_hash($password_raw, PASSWORD_DEFAULT);
        
        // We use email as the username for simplicity based on recent login changes
        $sql = "INSERT INTO users (username, password_hash, full_name, email, role) 
                VALUES ('$email', '$password_hash', '$full_name', '$email', 'employee')";
        
        if (mysqli_query($conn, $sql)) {
            $message = "Employee added successfully!";
        } else {
            $error = "Error adding employee: " . mysqli_error($conn);
        }
    } else {
        $error = "User with that email already exists.";
    }
}

// Fetch Employees
$employees = mysqli_query($conn, "SELECT * FROM users WHERE role='employee' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - SmartFusion Team</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
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
                <a href="manage_employees.php" class="nav-item active">Employees</a>
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
                <h3>Employee Management</h3>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-semibold"><?php echo $_SESSION['full_name']; ?></span>
                </div>
            </header>

            <div class="page-content">
                
                <?php if($message): ?>
                    <div style="background-color: #DCFCE7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div style="background-color: #FEE2E2; color: #991B1B; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="grid-3" style="grid-template-columns: 1fr 2fr;">
                    
                    <!-- Add Employee -->
                    <div class="card">
                        <h4 class="mb-4">Add New Employee</h4>
                        <form method="post">
                            <input type="hidden" name="action" value="add_employee">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" required placeholder="John Doe">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email (Login ID)</label>
                                <input type="email" name="email" class="form-control" required placeholder="john@company.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required placeholder="******">
                            </div>
                            <button type="submit" class="btn btn-primary w-full">Create Account</button>
                        </form>
                    </div>

                    <!-- Employee List -->
                    <div class="card">
                        <h4 class="mb-4">Employee Directory</h4>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Joined Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($employees) > 0): ?>
                                        <?php while($user = mysqli_fetch_assoc($employees)): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                                <td>
                                                    <?php if($user['is_active']): ?>
                                                        <span class="badge badge-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center text-muted">No employees found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            </div>
        </main>
    </div>
</body>
</html>
