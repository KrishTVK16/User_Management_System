<?php
// actions/update_leave_status.php
session_start();
require('../includes/db_connect.php');
require('../includes/auth_session.php');

check_login();
check_admin();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $leave_id = $_POST['leave_id'];
    $status = $_POST['status']; // 'Approved' or 'Rejected'
    $admin_comment = mysqli_real_escape_string($conn, $_POST['admin_comment']);

    $sql = "UPDATE leave_requests SET status = '$status', admin_comment = '$admin_comment' WHERE id = '$leave_id'";

    if (mysqli_query($conn, $sql)) {
        $_SESSION['message'] = "Request $status successfully.";
    } else {
        $_SESSION['error'] = "Error updating request: " . mysqli_error($conn);
    }

    header("Location: ../manage_leaves.php");
    exit();
}
?>