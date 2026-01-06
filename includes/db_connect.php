<?php
// includes/db_connect.php

// -------------------------------------------------------------------------
//  SMART DATABASE CONNECTION
//  Automatically detects if running on Live Server or Localhost
// -------------------------------------------------------------------------

$host = $_SERVER['HTTP_HOST'];

if ($host == 'localhost' || $host == '127.0.0.1') {
    // === LOCALHOST SETTINGS (XAMPP) ===
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "teampulse_db";
    $port = 3307; // Trying 3307 for your specific local setup
} else {
    // === LIVE SERVER SETTINGS (INFINITYFREE) ===
    $servername = "sql306.infinityfree.com";
    $username = "if0_40831119";
    $password = "SFkrishna11225";
    $dbname = "if0_40831119_smartfusionteam";
    $port = 3306; // Standard port for live
}

// Create Connection
// We suppress warnings (@) to handle errors gracefully manually
$conn = @new mysqli($servername, $username, $password, $dbname, $port);

// If Local 3307 fails, try 3306 (Standard Local Fallback)
if ($conn->connect_error && ($host == 'localhost' || $host == '127.0.0.1')) {
    $conn = @new mysqli($servername, $username, $password, $dbname, 3306);
}

// Check connection
if ($conn->connect_error) {
    die("<h3>Database Connection Failed</h3>
         <p><b>Error:</b> " . $conn->connect_error . "</p>
         <p><b>Environment:</b> " . ($host == 'localhost' ? 'Localhost' : 'Live Server') . "</p>
         <p>Please check your configuration in <code>includes/db_connect.php</code>.</p>");
}

// -------------------------------------------------------------------------
//  SET TIMEZONE (Fix for 8:30 vs 1:30 issue)
// -------------------------------------------------------------------------
date_default_timezone_set('Asia/Kolkata'); // Standard India Time
$conn->query("SET time_zone = '+05:30'");  // Force MySQL to use IST
?>