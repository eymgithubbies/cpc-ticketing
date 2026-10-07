<?php
require_once 'config.php';

// Check if Minerals department already exists
$check_sql = "SELECT id FROM departments WHERE name = 'Minerals'";
$result = $conn->query($check_sql);

if ($result->num_rows > 0) {
    echo "Minerals department already exists.";
} else {
    // Insert Minerals department
    $insert_sql = "INSERT INTO departments (name, description) VALUES ('Minerals', 'Minerals processing and management')";
    if ($conn->query($insert_sql)) {
        echo "Minerals department added successfully!";
    } else {
        echo "Error adding Minerals department: " . $conn->error;
    }
}
?>
