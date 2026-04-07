<?php
// includes/project_functions.php

/**
 * Log a project status change or action
 */
function log_project_history($conn, $project_id, $user_id, $action, $from_status, $to_status, $notes = "") {
    $action = mysqli_real_escape_string($conn, $action);
    $from_status = mysqli_real_escape_string($conn, $from_status);
    $to_status = mysqli_real_escape_string($conn, $to_status);
    $notes = mysqli_real_escape_string($conn, $notes);
    
    $sql = "INSERT INTO project_history (project_id, user_id, action, from_status, to_status, notes) 
            VALUES ('$project_id', '$user_id', '$action', '$from_status', '$to_status', '$notes')";
    return mysqli_query($conn, $sql);
}

/**
 * Create a notification for a user
 */
function create_notification($conn, $user_id, $message, $type = 'info') {
    $message = mysqli_real_escape_string($conn, $message);
    $sql = "INSERT INTO notifications (user_id, message, type) VALUES ('$user_id', '$message', '$type')";
    return mysqli_query($conn, $sql);
}

/**
 * Validate status transition
 */
function is_valid_transition($current_status, $new_status) {
    $workflow = [
        'Assigned' => ['Development Initialized'],
        'Development Initialized' => ['Development Completed'],
        'Development Completed' => ['Testing'],
        'Testing' => ['Correction Required', 'Finalized'],
        'Correction Required' => ['Corrected'],
        'Corrected' => ['Testing'],
        'Finalized' => ['Client Submitted'],
        'Client Submitted' => []
    ];
    
    if (!isset($workflow[$current_status])) return false;
    return in_array($new_status, $workflow[$current_status]);
}

/**
 * Check for delays (> 2 days in Dev Initialized/Corrected)
 */
function check_project_delays($conn) {
    $two_days_ago = date('Y-m-d H:i:s', strtotime('-2 days'));
    
    // Projects stuck in Dev Initialized
    $sql = "SELECT p.*, u.full_name as dev_name, t.full_name as tester_name 
            FROM projects p 
            JOIN users u ON p.developer_id = u.id 
            JOIN users t ON p.tester_id = t.id
            WHERE p.status IN ('Development Initialized', 'Corrected') 
            AND p.started_at < '$two_days_ago' 
            AND p.is_delayed = 0";
    
    $result = mysqli_query($conn, $sql);
    while ($project = mysqli_fetch_assoc($result)) {
        $id = $project['id'];
        $name = $project['name'];
        mysqli_query($conn, "UPDATE projects SET is_delayed = 1 WHERE id = $id");
        
        $msg = "Project '$name' is DELAYED (> 2 days in development).";
        create_notification($conn, $project['developer_id'], $msg, 'alert');
        create_notification($conn, $project['tester_id'], $msg, 'alert');
        
        // Notify Admins
        $admins = mysqli_query($conn, "SELECT id FROM users WHERE role IN ('admin', 'super_admin')");
        while ($admin = mysqli_fetch_assoc($admins)) {
            create_notification($conn, $admin['id'], "[ADMIN ALERT] $msg", 'alert');
        }
    }
}

/**
 * Check if developer has reached the initialization limit
 */
function can_developer_start_new($conn, $developer_id) {
    $sql = "SELECT COUNT(*) as count FROM projects 
            WHERE developer_id = '$developer_id' 
            AND status = 'Development Initialized'";
    $result = mysqli_fetch_assoc(mysqli_query($conn, $sql));
    
    if ($result['count'] >= 6) {
        $msg = "Warning: You have 6 or more projects in 'Development Initialized'. Please complete some before starting more.";
        create_notification($conn, $developer_id, $msg, 'warning');
        
        // Notify Admins
        $admins = mysqli_query($conn, "SELECT id FROM users WHERE role IN ('admin', 'super_admin')");
        while ($admin = mysqli_fetch_assoc($admins)) {
            create_notification($conn, $admin['id'], "[LIMIT WARNING] Developer has > 6 projects initialized.", 'warning');
        }
        return false;
    }
    return true;
}

/**
 * Track daily productivity
 */
function get_daily_completions($conn, $developer_id, $date = null) {
    if (!$date) $date = date('Y-m-d');
    $sql = "SELECT COUNT(*) as count FROM projects 
            WHERE developer_id = '$developer_id' 
            AND DATE(completed_at) = '$date'";
    $result = mysqli_fetch_assoc(mysqli_query($conn, $sql));
    return $result['count'];
}

/**
 * Check minimum productivity rule
 */
function check_productivity_rule($conn, $developer_id) {
    $count = get_daily_completions($conn, $developer_id);
    if ($count < 3) {
        // This is usually checked at the end of the day or in the dashboard
        return false;
    }
    return true;
}

/**
 * Get hidden status for filtering super admin
 */
function get_user_visibility_clause($session_role) {
    if ($session_role == 'super_admin') return "";
    return " AND role != 'super_admin'";
}
?>
