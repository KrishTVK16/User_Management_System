<?php
// actions/project_action.php
session_start();
require('../includes/db_connect.php');
require('../includes/auth_session.php');
require('../includes/project_functions.php');

check_login();

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$project_id = $_POST['project_id'] ?? 0;

if (!$project_id || !$action) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../employee_dashboard.php");
    exit();
}

// Fetch project details
$proj_sql = "SELECT * FROM projects WHERE id = '$project_id'";
$proj_res = mysqli_query($conn, $proj_sql);
$project = mysqli_fetch_assoc($proj_res);

if (!$project) {
    $_SESSION['error'] = "Project not found.";
    header("Location: ../employee_dashboard.php");
    exit();
}

$current_status = $project['status'];
$from_status = $current_status;
$to_status = '';
$redirect = "../employee_dashboard.php";

switch ($action) {
    case 'start_development':
        $to_status = 'Development Initialized';
        if (is_valid_transition($from_status, $to_status)) {
            // Rule: Check initialization limit
            if (can_developer_start_new($conn, $user_id)) {
                $notes = mysqli_real_escape_string($conn, $_POST['notes']);
                $now = date('Y-m-d H:i:s');
                $sql = "UPDATE projects SET status = '$to_status', started_at = '$now', initial_notes = '$notes' WHERE id = '$project_id'";
                if (mysqli_query($conn, $sql)) {
                    log_project_history($conn, $project_id, $user_id, "Started development", $from_status, $to_status, $notes);
                    $_SESSION['message'] = "Project development initialized successfully!";
                }
            } else {
                $_SESSION['error'] = "Limit reached! You cannot start more than 6 projects simultaneously.";
            }
        }
        break;

    case 'complete_development':
        $to_status = 'Development Completed';
        $next_status = 'Testing'; // Move to testing immediately for tester to see
        if (is_valid_transition($from_status, $to_status)) {
            $link = mysqli_real_escape_string($conn, $_POST['completion_link']);
            $notes = mysqli_real_escape_string($conn, $_POST['notes']);
            $now = date('Y-m-d H:i:s');
            
            $sql = "UPDATE projects SET 
                    status = '$next_status', 
                    completed_at = '$now', 
                    completion_link = '$link', 
                    completion_notes = '$notes' 
                    WHERE id = '$project_id'";
            
            if (mysqli_query($conn, $sql)) {
                log_project_history($conn, $project_id, $user_id, "Completed development & submitted for testing", $from_status, $next_status, $notes);
                
                // Notify Tester
                create_notification($conn, $project['tester_id'], "Developer completed project: " . $project['name'], 'info');
                $_SESSION['message'] = "Project submitted for testing!";
            }
        }
        break;

    case 'request_correction':
        $to_status = 'Correction Required';
        if (is_valid_transition('Testing', $to_status) || $from_status == 'Testing' || $from_status == 'Corrected') {
            $notes = mysqli_real_escape_string($conn, $_POST['notes']);
            $sql = "UPDATE projects SET status = '$to_status', fix_notes = '$notes' WHERE id = '$project_id'";
            
            if (mysqli_query($conn, $sql)) {
                log_project_history($conn, $project_id, $user_id, "Requested correction", $from_status, $to_status, $notes);
                
                // Notify Developer
                create_notification($conn, $project['developer_id'], "Tester requested correction for: " . $project['name'], 'alert');
                
                // Record in corrections table
                $ins_corr = "INSERT INTO project_corrections (project_id, tester_id, developer_id, correction_notes) 
                             VALUES ('$project_id', '$user_id', '".$project['developer_id']."', '$notes')";
                mysqli_query($conn, $ins_corr);
                
                $_SESSION['message'] = "Correction request sent to developer.";
            }
        }
        break;

    case 'finalize_project':
        $to_status = 'Finalized';
        if ($from_status == 'Testing' || $from_status == 'Corrected') {
            $now = date('Y-m-d H:i:s');
            $sql = "UPDATE projects SET status = '$to_status', finalized_at = '$now' WHERE id = '$project_id'";
            
            if (mysqli_query($conn, $sql)) {
                log_project_history($conn, $project_id, $user_id, "Finalized project", $from_status, $to_status);
                
                // Notify Admin
                $admins = mysqli_query($conn, "SELECT id FROM users WHERE role IN ('admin', 'super_admin')");
                while ($admin = mysqli_fetch_assoc($admins)) {
                    create_notification($conn, $admin['id'], "Project finalized by tester: " . $project['name'], 'info');
                }
                
                $_SESSION['message'] = "Project finalized successfully!";
            }
        }
        break;

    default:
        $_SESSION['error'] = "Unknown action.";
        break;
}

if (!isset($_SESSION['message']) && !isset($_SESSION['error'])) {
    $_SESSION['error'] = "Workflow error: Invalid status transition.";
}

header("Location: $redirect");
exit();
?>
