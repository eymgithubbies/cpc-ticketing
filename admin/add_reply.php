<?php
require_once '../config.php';

if (!is_logged_in() || !is_staff()) {
    redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ticket_id = (int)$_POST['ticket_id'];
    $message = sanitize_input($_POST['message']);
    $is_internal = isset($_POST['is_internal']) ? 1 : 0;
    $user_id = $_SESSION['user_id'];
    
    if (empty($message)) {
        redirect('view_ticket.php?id=' . $ticket_id . '&error=Message cannot be empty');
    }
    
    // Insert reply
    $sql = "INSERT INTO ticket_replies (ticket_id, user_id, message, is_internal) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisi", $ticket_id, $user_id, $message, $is_internal);
    
    if ($stmt->execute()) {
        // Update ticket updated_at
        $conn->query("UPDATE tickets SET updated_at = CURRENT_TIMESTAMP WHERE id = $ticket_id");
        
        // Log activity
        $activity_desc = $is_internal ? 'Internal note added by admin' : 'Reply added by admin';
        log_ticket_activity($conn, $ticket_id, $user_id, 'reply', $activity_desc);
        
        // Notify ticket owner (only for public replies, not internal notes)
        if (!$is_internal) {
            $owner_sql = "SELECT t.user_id, t.ticket_number FROM tickets t WHERE t.id = ?";
            $stmt2 = $conn->prepare($owner_sql);
            $stmt2->bind_param("i", $ticket_id);
            $stmt2->execute();
            $owner = $stmt2->get_result()->fetch_assoc();
            if ($owner) {
                create_notification($conn, $owner['user_id'], 'reply', "New Reply on Ticket " . $owner['ticket_number'], "An admin has replied to your ticket", "../employee/view_ticket.php?id=$ticket_id");
            }
        }
        
        redirect('view_ticket.php?id=' . $ticket_id . '&success=Reply added successfully');
    } else {
        redirect('view_ticket.php?id=' . $ticket_id . '&error=Failed to add reply');
    }
} else {
    redirect('all_tickets.php');
}
?>
