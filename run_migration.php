<?php
// run_migration.php
require('includes/db_connect.php');
mysqli_report(MYSQLI_REPORT_OFF);

$queries = [
    // 1. Users Table Updates
    "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'employee', 'super_admin') NOT NULL DEFAULT 'employee'",
    "ALTER TABLE users ADD COLUMN sub_role ENUM('Developer', 'Tester', 'Full Stack', 'None') DEFAULT 'None' AFTER role",

    // 2. Projects Table Updates
    "ALTER TABLE projects ADD COLUMN client_name VARCHAR(100) AFTER name",
    "ALTER TABLE projects ADD COLUMN project_link VARCHAR(255) AFTER client_name",
    "ALTER TABLE projects ADD COLUMN developer_id INT AFTER description",
    "ALTER TABLE projects ADD COLUMN tester_id INT AFTER developer_id",
    "ALTER TABLE projects ADD COLUMN assigned_at TIMESTAMP NULL AFTER tester_id",
    "ALTER TABLE projects ADD COLUMN started_at TIMESTAMP NULL AFTER assigned_at",
    "ALTER TABLE projects ADD COLUMN completed_at TIMESTAMP NULL AFTER started_at",
    "ALTER TABLE projects ADD COLUMN finalized_at TIMESTAMP NULL AFTER completed_at",
    "ALTER TABLE projects ADD COLUMN submitted_at TIMESTAMP NULL AFTER finalized_at",
    "ALTER TABLE projects ADD COLUMN initial_notes TEXT AFTER submitted_at",
    "ALTER TABLE projects ADD COLUMN completion_link VARCHAR(255) AFTER initial_notes",
    "ALTER TABLE projects ADD COLUMN completion_notes TEXT AFTER completion_link",
    "ALTER TABLE projects ADD COLUMN fix_notes TEXT AFTER completion_notes",
    "ALTER TABLE projects ADD COLUMN is_delayed TINYINT(1) DEFAULT 0 AFTER fix_notes",
    "ALTER TABLE projects MODIFY COLUMN status ENUM('Assigned', 'Development Initialized', 'Development Completed', 'Testing', 'Correction Required', 'Corrected', 'Finalized', 'Client Submitted') DEFAULT 'Assigned'",
    
    // Constraints (Added separately to avoid failures if they already exist)
    "ALTER TABLE projects ADD CONSTRAINT fk_developer FOREIGN KEY (developer_id) REFERENCES users(id) ON DELETE SET NULL",
    "ALTER TABLE projects ADD CONSTRAINT fk_tester FOREIGN KEY (tester_id) REFERENCES users(id) ON DELETE SET NULL",

    // 3. New Tables
    "CREATE TABLE IF NOT EXISTS project_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        user_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        from_status VARCHAR(50),
        to_status VARCHAR(50),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS project_corrections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        tester_id INT NOT NULL,
        developer_id INT NOT NULL,
        correction_notes TEXT NOT NULL,
        attachment_path VARCHAR(255),
        fix_notes TEXT,
        is_fixed TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fixed_at TIMESTAMP NULL,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (tester_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (developer_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'warning', 'alert') DEFAULT 'info',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "ALTER TABLE users ADD COLUMN reset_token_hash VARCHAR(64) NULL",
    "ALTER TABLE users ADD COLUMN reset_token_expiry DATETIME NULL",
    "UPDATE projects SET status = 'Assigned' WHERE status NOT IN ('Assigned', 'Development Initialized', 'Development Completed', 'Testing', 'Correction Required', 'Corrected', 'Finalized', 'Client Submitted')",
    
    // 4. Hierarchical Project Updates
    "ALTER TABLE projects ADD COLUMN parent_id INT NULL AFTER id",
    "ALTER TABLE projects ADD COLUMN requirements TEXT AFTER description",
    "ALTER TABLE projects ADD COLUMN project_type VARCHAR(100) DEFAULT 'Static HTML' AFTER requirements",
    "ALTER TABLE projects ADD COLUMN estimated_days INT DEFAULT 2 AFTER project_type",
    "ALTER TABLE projects ADD CONSTRAINT fk_project_parent FOREIGN KEY (parent_id) REFERENCES projects(id) ON DELETE CASCADE",
    "CREATE INDEX idx_project_parent ON projects(parent_id)",
    "INSERT INTO projects (name, description, status) VALUES ('March 2026 Slot', 'Master project for March website rollout', 'Assigned')",
    
    // 5. Rename Administrator Roles in Database
    "UPDATE users SET full_name = 'Administrator' WHERE full_name = 'Main Administrator'",
    "UPDATE users SET full_name = 'Administrator_' WHERE full_name = 'Super Administrator'"
];


echo "<h3>Starting Migration...</h3>";

foreach ($queries as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "<p style='color: green;'>✅ Executed: " . substr($sql, 0, 80) . "...</p>";
    } else {
        $errno = mysqli_errno($conn);
        $error_msg = mysqli_error($conn);

        // Common "Already Exists" error codes:
        // 1060: Duplicate column, 1061: Duplicate index, 121/1022/1005: Duplicate constraint/foreign key
        // 1050: Table exists, 1062: Duplicate entry
        $skipped_codes = [1050, 1060, 1061, 1062, 121, 1022, 1005];
        
        // Also check if the error message itself mentions "Duplicate" or "already exists"
        $is_duplicate = false;
        foreach (['duplicate', 'already exists', 'exists'] as $keyword) {
            if (stripos($error_msg, $keyword) !== false) {
                $is_duplicate = true;
                break;
            }
        }

        if (in_array($errno, $skipped_codes) || $is_duplicate) {
            echo "<p style='color: orange;'>⚠️ Skipped (Already exists): " . substr($sql, 0, 80) . "...</p>";
        } else {
            echo "<p style='color: red;'>❌ Error: $error_msg (Code: $errno)<br>Query: <code>$sql</code></p>";
        }
    }
}

echo "<h3>Migration Complete.</h3>";
echo "<p><a href='login.php'>Go to Login</a></p>";
?>
