<?php
// reset_password.php
require('includes/db_connect.php');

$token = $_GET['token'] ?? '';
$token_hash = hash('sha256', $token);
$message = "";
$error = "";

// Verify Token and Expiry
$query = "SELECT * FROM users WHERE reset_token_hash='$token_hash' AND reset_token_expiry > NOW()";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    $error = "The password reset link is invalid or has expired.";
} else {
    // If form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $password = $_REQUEST['password'];
        $confirm_password = $_REQUEST['confirm_password'];

        if ($password === $confirm_password) {
            if (strlen($password) >= 6) {
                // Update Password and Clear Token
                $new_password_hash = password_hash($password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE users SET password_hash='$new_password_hash', reset_token_hash=NULL, reset_token_expiry=NULL WHERE id='" . $user['id'] . "'";
                
                if (mysqli_query($conn, $update_sql)) {
                    $message = "Your password has been reset successfully!";
                } else {
                    $error = "Database error. Please try again later.";
                }
            } else {
                $error = "Password must be at least 6 characters long.";
            }
        } else {
            $error = "Passwords do not match.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reset Password - SmartFusion Team</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>

    <div class="login-page">
        <div class="card login-card">
            <div class="login-header">
                <div class="logo-container">
                    <img src="assets/logo.png" alt="SmartFusion Logo" class="logo-img">
                </div>
                <h2 class="logo-text-login">SmartFusion</h2>
                <p class="text-muted mt-2">Create your new password</p>
            </div>

            <?php if ($message): ?>
                <div style="background-color: #DCFCE7; color: #166534; border: 2px solid #166534; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; text-align: center; font-size: 0.9rem; font-weight: 600;">
                    <?php echo $message; ?>
                </div>
                <div class="text-center mt-4">
                    <a href="login.php" class="btn btn-primary w-full">Sign In Now</a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div style="background-color: #2A1A1A; color: #EF4444; border: 2px solid #EF4444; padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; text-align: center; font-size: 0.9rem; font-weight: 700;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($user): ?>
                    <form method="post">
                        <div class="form-group">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter new password" required minlength="6">
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-enter new password" required minlength="6">
                        </div>

                        <button type="submit" class="btn btn-primary w-full">Update Password</button>
                    </form>
                <?php else: ?>
                    <div class="text-center mt-4">
                        <a href="forgot_password.php" class="btn btn-outline w-full">Request New Link</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="text-center mt-6">
                <a href="login.php" class="text-sm text-sub hover:underline">Back to Login</a>
            </div>
        </div>
    </div>

</body>

</html>
