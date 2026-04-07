<?php
// login.php
session_start();
require('includes/db_connect.php');

$error_msg = "";

// If form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = stripslashes($_REQUEST['username']);
    $username = mysqli_real_escape_string($conn, $username);
    $password = stripslashes($_REQUEST['password']);
    $password = mysqli_real_escape_string($conn, $password);

    // Check if user exists
    $query = "SELECT * FROM `users` WHERE username='$username'";
    $result = mysqli_query($conn, $query) or die(mysql_error());
    $rows = mysqli_num_rows($result);

    if ($rows == 1) {
        $user = mysqli_fetch_assoc($result);

        // Verify Password
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];

            // Redirect based on role
            if ($user['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: employee_dashboard.php");
            }
            exit();
        } else {
            $error_msg = "Incorrect password.";
        }
    } else {
        $error_msg = "User not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login - SmartFusion Team</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <!-- CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css?v=1.1">
</head>

<body>

    <div class="login-page">
        <div class="card login-card">
            <div class="login-header">
                <div class="logo-container">
                    <img src="assets/logo.png" alt="SmartFusion Logo" class="logo-img">
                </div>
                <!-- <p class="text-muted">Smart Fusion Corporate Solutions</p> -->
                <h2 class="logo-text-login">SmartFusion</h2>
            </div>

            <?php if ($error_msg): ?>
                <div
                    style="background-color: #2A1A1A; color: #EF4444; border: 2px solid #EF4444; padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1rem; text-align: center; font-size: 0.9rem; font-weight: 700;">
                    <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <form action="" method="post">
                <div class="form-group">
                    <label for="username" class="form-label">Email / Login ID</label>
                    <input type="text" name="username" id="username" class="form-control"
                        placeholder="Enter your email ID" required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control"
                        placeholder="Enter your password" required>
                </div>

                <!-- Helper for Demo -->
                <!-- Credentials removed as requested -->

                <div class="form-group flex justify-between items-center flex-wrap gap-2">
                    <label class="flex items-center gap-2 text-sm text-sub nowrap">
                        <input type="checkbox"> Remember me
                    </label>
                    <a href="forgot_password.php" class="text-sm text-gold hover:underline"
                        style="color: var(--primary-color);">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-primary w-full">Sign In</button>
            </form>
            <div style="position: absolute; bottom: 1rem; right: 1rem; font-size: 0.75rem; color: var(--text-sub); background: rgba(255,255,255,0.05); padding: 0.3rem 0.6rem; border-radius: 4px; border: 1px solid var(--border-color); font-weight: 600;">
                v1.1
            </div>
        </div>
    </div>

</body>

</html>