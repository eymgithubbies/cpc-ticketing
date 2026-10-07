<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    require_once '../config.php';

    if (!is_logged_in() || !is_staff()) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $admin_id = $_SESSION['user_id'];
        $current_activity = sanitize_input($_POST['current_activity'] ?? '');
        $status = sanitize_input($_POST['status'] ?? 'Available');
        $location = sanitize_input($_POST['location'] ?? '');
        $related_ticket_id = !empty($_POST['related_ticket_id']) ? intval($_POST['related_ticket_id']) : null;

        // Validate status
        $valid_statuses = ['Available', 'Busy', 'On-site', 'In Progress', 'Break'];
        if (!in_array($status, $valid_statuses)) {
            $status = 'Available';
        }

        // Check if admin already has a status record
        try {
            $check_sql = "SELECT id FROM it_status WHERE admin_id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            $result = $stmt->get_result();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Table it_status does not exist. Please run create_it_status_table.php']);
            exit();
        }

        if ($result->num_rows > 0) {
            // Update existing record
            $row = $result->fetch_assoc();
            $status_id = $row['id'];
            
            $update_sql = "UPDATE it_status 
                           SET current_activity = ?, status = ?, location = ?, 
                               related_ticket_id = ?, estimated_finish = NULL
                           WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sssis", $current_activity, $status, $location, $related_ticket_id, $status_id);
        } else {
            // Insert new record
            $insert_sql = "INSERT INTO it_status (admin_id, current_activity, status, location, related_ticket_id, estimated_finish)
                           VALUES (?, ?, ?, ?, ?, NULL)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("isssi", $admin_id, $current_activity, $status, $location, $related_ticket_id);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save status: ' . $stmt->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>
