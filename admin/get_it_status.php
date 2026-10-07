<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        // Get the most recent IT status (from any admin)
        $sql = "SELECT s.*, u.full_name as admin_name 
                FROM it_status s 
                LEFT JOIN users u ON s.admin_id = u.id 
                ORDER BY s.updated_at DESC 
                LIMIT 1";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $status = $result->fetch_assoc();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $status]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No status found']);
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Table not found']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>
