<?php
require_once '../config.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false]);
    exit();
}

$notification_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

if ($notification_id > 0) {
    $result = mark_notification_read($conn, $notification_id, $user_id);
    echo json_encode(['success' => $result]);
} else {
    echo json_encode(['success' => false]);
}
?>
