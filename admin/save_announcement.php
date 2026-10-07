<?php
require_once '../config.php';

if (!is_logged_in() || !is_staff()) {
    redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('announcements.php');
}

$title = sanitize_input($_POST['title']);
$content = sanitize_input($_POST['content']);
$priority = sanitize_input($_POST['priority']);
$expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
$created_by = $_SESSION['user_id'];

// Validation
if (empty($title) || empty($content)) {
    redirect('announcements.php?error=Title and content are required');
}

// Insert announcement
if ($expires_at) {
    $stmt = $conn->prepare("INSERT INTO announcements (title, content, created_by, priority, expires_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiss", $title, $content, $created_by, $priority, $expires_at);
} else {
    $stmt = $conn->prepare("INSERT INTO announcements (title, content, created_by, priority) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $title, $content, $created_by, $priority);
}

if ($stmt->execute()) {
    redirect('announcements.php?success=Announcement posted successfully');
} else {
    redirect('announcements.php?error=Failed to post announcement');
}
?>
