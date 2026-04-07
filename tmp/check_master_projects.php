<?php
require('includes/db_connect.php');

$query = "SELECT id, name, created_at FROM projects WHERE parent_id IS NULL ORDER BY name ASC";
$result = $conn->query($query);

echo "Master Projects Found:\n";
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Created At: " . $row['created_at'] . "\n";
}
?>
