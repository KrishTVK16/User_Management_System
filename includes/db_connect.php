<?php
// includes/db_connect.php

$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = "";     // Default XAMPP password (empty)
$dbname = "teampulse_db";

// Create connection
// Connection Attempt 1: Try Port 3307 (Common XAMPP Fix)
$conn = @new mysqli($servername, $username, $password, $dbname, 3307);

// Connection Attempt 2: If 3307 failed, try default Port 3306
if ($conn->connect_error) {
    $conn = @new mysqli($servername, $username, $password, $dbname, 3306);
}

// Final Check
if ($conn->connect_error) {
    die("Connection failed (Tried ports 3307 and 3306). Error: " . $conn->connect_error);
}
?>