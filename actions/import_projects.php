<?php
// actions/import_projects.php
session_start();
require('../includes/db_connect.php');
require('../includes/auth_session.php');

check_login();
if ($_SESSION['role'] !== 'admin') {
    die("Unauthorized access.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['project_file'])) {
    $parent_id = $_POST['parent_id'];
    $project_type = mysqli_real_escape_string($conn, $_POST['project_type'] ?? 'Static HTML');
    $file = $_FILES['project_file']['tmp_name'];

    if (!is_uploaded_file($file)) {
        $_SESSION['error'] = "No file chosen or upload error.";
        header("Location: ../manage_projects.php");
        exit();
    }

    $handle = fopen($file, "r");
    $rowCount = 0;
    $importCount = 0;
    $errors = [];

    // Header validation (Optional but good)
    $headers = fgetcsv($handle); 
    // Expectations: WEBSITE NAME, WEBSITE REQUIREMENTS
    
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $rowCount++;
        
        // Basic mapping based on user screenshot
        $name = mysqli_real_escape_string($conn, $data[0] ?? '');
        $requirements = mysqli_real_escape_string($conn, $data[1] ?? '');
        
        if (empty($name)) {
            continue; // Skip empty rows
        }

        $now = date('Y-m-d H:i:s');
        
        // Insert sub-project
        $sql = "INSERT INTO projects (parent_id, name, requirements, project_type, status, created_at) 
                VALUES ('$parent_id', '$name', '$requirements', '$project_type', 'Assigned', '$now')";
        
        if (mysqli_query($conn, $sql)) {
            $importCount++;
        } else {
            $errors[] = "Row $rowCount: " . mysqli_error($conn);
        }
    }

    fclose($handle);

    if ($importCount > 0) {
        $_SESSION['message'] = "Successfully imported $importCount websites under the master project.";
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = "Imported $importCount rows, but had " . count($errors) . " errors. First error: " . $errors[0];
    }

    header("Location: ../manage_projects.php");
    exit();
}
?>
