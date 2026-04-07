<?php
require('includes/db_connect.php');

// Fetch all master projects
$query = "SELECT id, name, created_at FROM projects WHERE parent_id IS NULL ORDER BY name ASC, created_at ASC";
$result = $conn->query($query);

$masters = [];
while ($row = $result->fetch_assoc()) {
    $masters[$row['name']][] = $row;
}

echo "Master Project Grouping:\n";
foreach ($masters as $name => $list) {
    echo "Name: $name (" . count($list) . " instances)\n";
    foreach ($list as $mp) {
        // Count sub-projects
        $sub_query = "SELECT COUNT(*) as count FROM projects WHERE parent_id = " . $mp['id'];
        $sub_res = $conn->query($sub_query);
        $sub_count = $sub_res->fetch_assoc()['count'];
        echo "  - ID: " . $mp['id'] . " | Created: " . $mp['created_at'] . " | Sub-projects: $sub_count\n";
    }
}
?>
