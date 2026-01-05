<?php
// includes/auth_session.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function check_login()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

// Function to check if user is admin
function check_admin()
{
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        // Redirect non-admins to their dashboard or show error
        header("Location: employee_dashboard.php");
        exit();
    }
}
?>