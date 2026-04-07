<?php
// forgot_password.php
require('includes/db_connect.php');

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Check if email exists
    $query = "SELECT * FROM users WHERE email='$email' AND is_active=1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        // Generate Token
        $token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token);
        $expiry = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // Store Token
        $update_sql = "UPDATE users SET reset_token_hash='$token_hash', reset_token_expiry='$expiry' WHERE id='" . $user['id'] . "'";
        if (mysqli_query($conn, $update_sql)) {
            // In a real system, we would send an email here.
            // For this demo, we'll display the link.
            $reset_link = "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=$token";
            $message = "A password reset link has been generated. <br><br> <a href='$reset_link' style='color: var(--primary-color); word-break: break-all;'>$reset_link</a>";
        } else {
            $error = "Database error. Please try again later.";
        }
    } else {
        // For security, don't reveal if the email exists or not.
        // But for this UMS, we'll be helpful.
        $error = "No active account found with that email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Forgot Password - SmartFusion Team</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
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
                <h2 class="logo-text-login">SmartFusion</h2>
                <p class="text-muted mt-2">Reset your account password</p>
            </div>

            <?php if ($message): ?>
                <div style="background-color: #1B2B3D; color: var(--primary-color); border: 2px solid var(--primary-color); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; text-align: center; font-size: 0.9rem;">
                    <?php echo $message; ?>
                </div>
                <div class="text-center mt-4">
                    <a href="login.php" class="btn btn-outline w-full">Back to Login</a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div style="background-color: #2A1A1A; color: #EF4444; border: 2px solid #EF4444; padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1rem; text-align: center; font-size: 0.9rem; font-weight: 700;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="Enter your registered email" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-full">Send Reset Link</button>
                    
                    <div class="text-center mt-6">
                        <a href="login.php" class="text-sm text-sub hover:underline">Back to Login</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>
