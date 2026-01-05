<?php
// setup_leaves_table.php
require('includes/db_connect.php');

$sql = "CREATE TABLE IF NOT EXISTS leave_requests (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    type ENUM('Full Day Leave', 'Half Day', 'Time Permission') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    admin_comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'leave_requests' created successfully (or already exists).";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}
?>