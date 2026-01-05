<?php
// profile.php
session_start();
require('includes/db_connect.php');
require('includes/auth_session.php');

check_login();

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Handle Password Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_password') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        if (strlen($new_password) >= 6) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password_hash='$password_hash' WHERE id='$user_id'";
            if (mysqli_query($conn, $sql)) {
                $message = "Password updated successfully!";
            } else {
                $error = "Error updating password: " . mysqli_error($conn);
            }
        } else {
            $error = "Password must be at least 6 characters long.";
        }
    } else {
        $error = "Passwords do not match.";
    }
}

// Fetch Current User Data
$user_sql = "SELECT * FROM users WHERE id='$user_id'";
$user = mysqli_fetch_assoc(mysqli_query($conn, $user_sql));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - SmartFusion Team</title>
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
                <a href="my_projects.php" class="nav-item">My Projects</a>
                <a href="my_leaves.php" class="nav-item">Leaves & Permissions</a>
                <a href="my_leaves.php" class="nav-item">Leaves & Permissions</a>
                <a href="my_history.php" class="nav-item">History</a>
                <a href="profile.php" class="nav-item active">Profile</a>
            </nav>
            <div class="sidebar-header" style="border-top: 1px solid #334155;">
                <a href="logout.php" class="nav-item" style="color: #EF4444;">Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <button class="menu-toggle">☰</button>
                <h3>My Profile</h3>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-semibold">
                        <?php echo $user['full_name']; ?>
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

                    <!-- Profile Details -->
                    <div class="card">
                        <h4 class="mb-4">My Details</h4>
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control"
                                value="<?php echo htmlspecialchars($user['full_name']); ?>" disabled
                                style="background: #F1F5F9;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email / Login ID</label>
                            <input type="text" class="form-control"
                                value="<?php echo htmlspecialchars($user['email']); ?>" disabled
                                style="background: #F1F5F9;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>"
                                disabled style="background: #F1F5F9;">
                        </div>
                        <p class="text-muted text-sm mt-4">To change your name or email, please contact an
                            administrator.</p>
                    </div>

                    <!-- Change Password -->
                    <div class="card">
                        <h4 class="mb-4">Change Password</h4>
                        <form method="post">
                            <input type="hidden" name="action" value="update_password">
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required minlength="6">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required
                                    minlength="6">
                            </div>
                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </form>
                    </div>

                </div>

            </div>
        </main>
    </div>
</body>

</html>