<?php
// Century Peak Cement - Ticketing System Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// SET PHILIPPINES TIMEZONE
// ==========================================
date_default_timezone_set('Asia/Manila');

// Database configuration - Docker compatible
$db_host = getenv('DB_HOST') ?: 'db';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASSWORD') ?: 'rootpassword';
$db_name = getenv('DB_NAME') ?: 'cpc_ticketing';

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Set MySQL timezone to Philippines
$conn->query("SET time_zone = '+08:00'");

// Helper functions
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $conn->real_escape_string($data);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_staff() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'staff';
}

function get_session_full_name($default = 'User') {
    return isset($_SESSION['full_name']) && $_SESSION['full_name'] ? $_SESSION['full_name'] : $default;
}

function get_session_department() {
    return isset($_SESSION['department']) ? $_SESSION['department'] : '';
}

// Generate unique ticket number
function generate_ticket_number() {
    return 'CPC-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
}

// Status badge colors
function get_status_badge($status) {
    $badges = [
        'open' => ['bg' => '#e3f2fd', 'color' => '#1976d2'],
        'in_progress' => ['bg' => '#fff3e0', 'color' => '#f57c00'],
        'resolved' => ['bg' => '#e8f5e8', 'color' => '#388e3c'],
        'closed' => ['bg' => '#fce4ec', 'color' => '#c2185b']
    ];
    return $badges[$status] ?? $badges['open'];
}

// Priority badge colors
function get_priority_badge($priority) {
    $badges = [
        'low' => ['bg' => '#e8f5e8', 'color' => '#2e7d32'],
        'normal' => ['bg' => '#e3f2fd', 'color' => '#1565c0'],
        'high' => ['bg' => '#fff3e0', 'color' => '#ef6c00'],
        'urgent' => ['bg' => '#ffebee', 'color' => '#c62828']
    ];
    return $badges[$priority] ?? $badges['normal'];
}

// Get active announcements
function get_active_announcements($conn, $limit = 5) {
    $sql = "SELECT * FROM announcements
            WHERE is_active = 1
            AND (expires_at IS NULL OR expires_at > NOW())
            ORDER BY priority DESC, created_at DESC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result();
}

// Log ticket activity
function log_ticket_activity($conn, $ticket_id, $user_id, $activity_type, $description, $old_value = null, $new_value = null) {
    $sql = "INSERT INTO ticket_activities (ticket_id, user_id, activity_type, description, old_value, new_value)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissss", $ticket_id, $user_id, $activity_type, $description, $old_value, $new_value);
    return $stmt->execute();
}

// Get ticket activities
function get_ticket_activities($conn, $ticket_id, $limit = 50) {
    $sql = "SELECT ta.*, u.full_name as user_name, u.user_type
            FROM ticket_activities ta
            LEFT JOIN users u ON ta.user_id = u.id
            WHERE ta.ticket_id = ?
            ORDER BY ta.created_at DESC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $ticket_id, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

// Create notification
function create_notification($conn, $user_id, $type, $title, $message, $link = null) {
    $sql = "INSERT INTO notifications (user_id, type, title, message, link)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $user_id, $type, $title, $message, $link);
    return $stmt->execute();
}

// Get unread notifications count
function get_unread_notifications_count($conn, $user_id) {
    $sql = "SELECT COUNT(*) as count FROM notifications
            WHERE user_id = ? AND is_read = FALSE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['count'] ?? 0;
}

// Get notifications
function get_notifications($conn, $user_id, $limit = 10) {
    $sql = "SELECT * FROM notifications
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

// Mark notification as read
function mark_notification_read($conn, $notification_id, $user_id) {
    $sql = "UPDATE notifications
            SET is_read = TRUE
            WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notification_id, $user_id);
    return $stmt->execute();
}

// Mark all notifications as read
function mark_all_notifications_read($conn, $user_id) {
    $sql = "UPDATE notifications
            SET is_read = TRUE
            WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    return $stmt->execute();
}

// Notify all staff members
function notify_all_staff($conn, $type, $title, $message, $link = null) {
    $sql = "INSERT INTO notifications (user_id, type, title, message, link)
            SELECT id, ?, ?, ?, ?
            FROM users
            WHERE user_type = 'staff'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $type, $title, $message, $link);
    return $stmt->execute();
}

// Time ago function
function time_ago($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins == 1 ? '1 min ago' : $mins . ' mins ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours == 1 ? '1 hour ago' : $hours . ' hours ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days == 1 ? '1 day ago' : $days . ' days ago';
    } else {
        return date('M j, Y', $time);
    }
}

// Simple cache for static data
function get_cached_departments($conn) {
    static $departments = null;
    if ($departments === null) {
        $result = $conn->query("SELECT id, name FROM departments ORDER BY name");
        $departments = [];
        while ($row = $result->fetch_assoc()) {
            $departments[$row['id']] = $row['name'];
        }
    }
    return $departments;
}

function get_cached_staff($conn) {
    static $staff = null;
    if ($staff === null) {
        $result = $conn->query("SELECT id, full_name FROM users WHERE user_type = 'staff' ORDER BY full_name");
        $staff = [];
        while ($row = $result->fetch_assoc()) {
            $staff[$row['id']] = $row['full_name'];
        }
    }
    return $staff;
}
?>