<?php
// actions/system_cleanup.php
session_start();
require('../includes/db_connect.php');
require('../includes/auth_session.php');

check_login();
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin') {
    die("Unauthorized access.");
}

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];

    // 1. MERGE MASTER PROJECTS (SLOTS)
    if ($action === 'merge_slots') {
        // Find duplicate names where parent_id is NULL
        $sql = "SELECT name, COUNT(*) as count 
                FROM projects 
                WHERE parent_id IS NULL 
                GROUP BY name 
                HAVING count > 1";
        $duplicates = mysqli_query($conn, $sql);
        
        $merge_count = 0;
        $delete_count = 0;

        while ($dup = mysqli_fetch_assoc($duplicates)) {
            $name = mysqli_real_escape_string($conn, $dup['name']);
            
            // Get all IDs for this name, ordered by earliest creation
            $id_sql = "SELECT id FROM projects WHERE name='$name' AND parent_id IS NULL ORDER BY created_at ASC";
            $id_res = mysqli_query($conn, $id_sql);
            
            $ids = [];
            while ($row = mysqli_fetch_assoc($id_res)) {
                $ids[] = $row['id'];
            }
            
            $keep_id = array_shift($ids); // The canonical ID
            $discard_ids = implode(',', $ids);
            
            // 1. Move sub-projects to the keep_id
            $move_sql = "UPDATE projects SET parent_id = $keep_id WHERE parent_id IN ($discard_ids)";
            if (mysqli_query($conn, $move_sql)) {
                $merge_count += mysqli_affected_rows($conn);
            }
            
            // 2. Delete the duplicate master entries
            $del_sql = "DELETE FROM projects WHERE id IN ($discard_ids)";
            if (mysqli_query($conn, $del_sql)) {
                $delete_count += count($ids);
            }
        }
        $message = "Merged $merge_count sub-projects and removed $delete_count duplicate slots.";
    }

    // 2. DELETE INDIVIDUAL PROJECT/SLOT
    elseif ($action === 'delete_project') {
        $project_id = (int)$_POST['id'];
        $sql = "DELETE FROM projects WHERE id = $project_id";
        if (mysqli_query($conn, $sql)) {
            $message = "Project deleted successfully.";
        } else {
            $error = "Error deleting project: " . mysqli_error($conn);
        }
    }

    // 3. DELETE USER
    elseif ($action === 'delete_user') {
        $user_id = (int)$_POST['id'];
        
        // Safety: Cannot delete self
        if ($user_id === $_SESSION['user_id']) {
            $error = "You cannot delete your own account.";
        } else {
            // Check if user is super_admin
            $check_sql = "SELECT role FROM users WHERE id = $user_id";
            $res = mysqli_fetch_assoc(mysqli_query($conn, $check_sql));
            
            if ($res['role'] === 'super_admin' && $_SESSION['role'] !== 'super_admin') {
                $error = "Only a Super Admin can delete another Super Admin.";
            } else {
                $sql = "DELETE FROM users WHERE id = $user_id";
                if (mysqli_query($conn, $sql)) {
                    $message = "User deleted successfully.";
                } else {
                    $error = "Error deleting user: " . mysqli_error($conn);
                }
            }
        }
    }

    // Redirect back to cleanup page with status
    $_SESSION['message'] = $message;
    $_SESSION['error'] = $error;
    header("Location: ../admin_cleanup.php");
    exit();
}
?>
