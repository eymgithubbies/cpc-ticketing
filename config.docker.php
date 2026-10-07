<?php
// Docker Configuration - Use this for Docker deployment

// Get environment variables or use defaults
$db_host = getenv('DB_HOST') ?: 'db';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASSWORD') ?: 'rootpassword';
$db_name = getenv('DB_NAME') ?: 'cpc_ticketing';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Helper functions
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
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

function get_session_full_name($type = null) {
    if ($type === 'Admin') {
        return $_SESSION['full_name'] ?? 'Administrator';
    }
    return $_SESSION['full_name'] ?? 'User';
}

// Ticket activity logging
function log_ticket_activity($conn, $ticket_id, $user_id, $activity_type, $description, $old_value = null, $new_value = null) {
    $sql = "INSERT INTO ticket_activities (ticket_id, user_id, activity_type, description, old_value, new_value) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissss", $ticket_id, $user_id, $activity_type, $description, $old_value, $new_value);
    return $stmt->execute();
}

// Get ticket activities
function get_ticket_activities($conn, $ticket_id) {
    $sql = "SELECT ta.*, u.full_name, u.username 
            FROM ticket_activities ta 
            LEFT JOIN users u ON ta.user_id = u.id 
            WHERE ta.ticket_id = ? 
            ORDER BY ta.created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Get active announcements
function get_active_announcements($conn, $limit = 5) {
    $sql = "SELECT * FROM announcements 
            WHERE is_active = TRUE 
            AND (expires_at IS NULL OR expires_at > NOW()) 
            ORDER BY priority DESC, created_at DESC 
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result();
}

// Badge styling
function get_priority_badge($priority) {
    $badges = [
        'low' => '<span style="background: #e8f5e9; color: #2e7d32; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;">Low</span>',
        'normal' => '<span style="background: #e3f2fd; color: #1565c0; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;">Normal</span>',
        'high' => '<span style="background: #fff3e0; color: #e65100; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;">High</span>',
        'urgent' => '<span style="background: #ffebee; color: #c62828; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;">Urgent</span>'
    ];
    return $badges[$priority] ?? $priority;
}

function get_status_badge($status) {
    $badges = [
        'open' => '<span style="background: #e8f5e9; color: #2e7d32; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;">Open</span>',
        'in_progress' => '<span style="background: #fff3e0; color: #e65100; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;">In Progress</span>',
        'resolved' => '<span style="background: #e3f2fd; color: #1565c0; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;">Resolved</span>',
        'closed' => '<span style="background: #f5f5f5; color: #616161; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;">Closed</span>'
    ];
    return $badges[$status] ?? $status;
}
?>
