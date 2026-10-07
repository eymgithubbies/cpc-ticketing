<?php
require_once '../config.php';

if (!is_logged_in() || !is_staff()) {
    echo json_encode(['success' => false]);
    exit();
}

$user_id = $_SESSION['user_id'];
$result = mark_all_notifications_read($conn, $user_id);
echo json_encode(['success' => $result]);
?>
