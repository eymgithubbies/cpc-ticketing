<?php
require_once '../config.php';

if (!is_logged_in()) {
    redirect('../index.php');
}

if (is_staff()) {
    redirect('../admin/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ticket_id = (int)$_POST['ticket_id'];
    $message = sanitize_input($_POST['message']);
    $user_id = $_SESSION['user_id'];
    
    // Verify ticket belongs to user and is not closed
    $check_sql = "SELECT status FROM tickets WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $ticket_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        redirect('my_tickets.php?error=Ticket not found');
    }
    
    $ticket = $result->fetch_assoc();
    if ($ticket['status'] === 'closed') {
        redirect('view_ticket.php?id=' . $ticket_id . '&error=Cannot reply to closed ticket');
    }
    
    if (empty($message)) {
        redirect('view_ticket.php?id=' . $ticket_id . '&error=Message cannot be empty');
    }
    
    // Insert reply
    $sql = "INSERT INTO ticket_replies (ticket_id, user_id, message, is_internal) VALUES (?, ?, ?, 0)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $ticket_id, $user_id, $message);
    
    if ($stmt->execute()) {
        // Log activity
        log_ticket_activity($conn, $ticket_id, $user_id, 'reply', 'Follow-up added by user');
        
        // Notify all staff about user reply
        $ticket_info_sql = "SELECT ticket_number FROM tickets WHERE id = ?";
        $stmt2 = $conn->prepare($ticket_info_sql);
        $stmt2->bind_param("i", $ticket_id);
        $stmt2->execute();
        $ticket_info = $stmt2->get_result()->fetch_assoc();
        if ($ticket_info) {
            $ticket_number = $ticket_info['ticket_number'];
            notify_all_staff($conn, 'user_reply', "User Reply on Ticket $ticket_number", "A user has replied to ticket $ticket_number", "../admin/view_ticket.php?id=$ticket_id");
        }
        
        redirect('view_ticket.php?id=' . $ticket_id . '&success=Reply added successfully');
    } else {
        redirect('view_ticket.php?id=' . $ticket_id . '&error=Failed to add reply');
    }
} else {
    redirect('my_tickets.php');
}
?>
