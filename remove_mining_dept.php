<?php
require_once 'config.php';

// Delete Mining department
$delete_sql = "DELETE FROM departments WHERE name = 'Mining'";
if ($conn->query($delete_sql)) {
    if ($conn->affected_rows > 0) {
        echo "Mining department removed successfully!";
    } else {
        echo "Mining department not found in database.";
    }
} else {
    echo "Error removing Mining department: " . $conn->error;
}
?>
