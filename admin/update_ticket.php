<?php
require_once '../config.php';

if (!is_logged_in() || !is_staff()) {
    redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ticket_id = (int)$_POST['ticket_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if delete action
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        // Delete ticket and related replies
        $conn->query("DELETE FROM ticket_replies WHERE ticket_id = $ticket_id");
        $conn->query("DELETE FROM ticket_activities WHERE ticket_id = $ticket_id");
        $conn->query("DELETE FROM tickets WHERE id = $ticket_id");
        redirect('all_tickets.php?success=Ticket deleted successfully');
    }
    
    // Get current ticket data for comparison
    $current_sql = "SELECT assigned_to, status, priority FROM tickets WHERE id = ?";
    $stmt = $conn->prepare($current_sql);
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    
    // Update ticket
    $assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : NULL;
    $status = sanitize_input($_POST['status']);
    $priority = sanitize_input($_POST['priority']);
    
    // Determine resolved/closed timestamps
    $resolved_at = NULL;
    $closed_at = NULL;
    
    if ($status === 'resolved') {
        $resolved_at = date('Y-m-d H:i:s');
    } elseif ($status === 'closed') {
        $closed_at = date('Y-m-d H:i:s');
        // If closing, also set resolved if not already
        $check_sql = "SELECT resolved_at FROM tickets WHERE id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("i", $ticket_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if (!$result['resolved_at']) {
            $resolved_at = date('Y-m-d H:i:s');
        }
    }
    
    $sql = "UPDATE tickets SET assigned_to = ?, status = ?, priority = ?, resolved_at = COALESCE(?, resolved_at), closed_at = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssi", $assigned_to, $status, $priority, $resolved_at, $closed_at, $ticket_id);
    
    if ($stmt->execute()) {
        // Log activities for changes
        
        // Get ticket owner for notifications
        $owner_sql = "SELECT user_id, ticket_number FROM tickets WHERE id = ?";
        $stmt2 = $conn->prepare($owner_sql);
        $stmt2->bind_param("i", $ticket_id);
        $stmt2->execute();
        $owner = $stmt2->get_result()->fetch_assoc();
        $ticket_owner_id = $owner['user_id'];
        $ticket_number = $owner['ticket_number'];
        
        // Status change
        if ($current['status'] !== $status) {
            log_ticket_activity($conn, $ticket_id, $user_id, 'status_change', 'Ticket status updated', $current['status'], $status);
            // Notify ticket owner
            $status_label = ucfirst(str_replace('_', ' ', $status));
            create_notification($conn, $ticket_owner_id, 'status_update', "Ticket $ticket_number Updated", "Your ticket status has been changed to: $status_label", "../employee/view_ticket.php?id=$ticket_id");
        }
        
        // Assignment change
        if ($current['assigned_to'] != $assigned_to) {
            if ($assigned_to) {
                // Get assignee name
                $assignee_sql = "SELECT full_name FROM users WHERE id = ?";
                $stmt = $conn->prepare($assignee_sql);
                $stmt->bind_param("i", $assigned_to);
                $stmt->execute();
                $assignee = $stmt->get_result()->fetch_assoc();
                log_ticket_activity($conn, $ticket_id, $user_id, 'assignment', 'Ticket assigned to ' . ($assignee['full_name'] ?? 'staff'), $current['assigned_to'] ? 'Assigned' : 'Unassigned', $assignee['full_name'] ?? 'Staff');
                // Notify ticket owner
                create_notification($conn, $ticket_owner_id, 'assignment', "Ticket $ticket_number Assigned", "Your ticket has been assigned to: " . ($assignee['full_name'] ?? 'Staff'), "../employee/view_ticket.php?id=$ticket_id");
            } else {
                log_ticket_activity($conn, $ticket_id, $user_id, 'assignment', 'Ticket unassigned', 'Assigned', 'Unassigned');
            }
        }
        
        // Priority change
        if ($current['priority'] !== $priority) {
            log_ticket_activity($conn, $ticket_id, $user_id, 'priority_change', 'Priority level changed', $current['priority'], $priority);
            // Notify ticket owner
            $priority_label = ucfirst($priority);
            create_notification($conn, $ticket_owner_id, 'priority_update', "Ticket $ticket_number Priority Updated", "Your ticket priority has been changed to: $priority_label", "../employee/view_ticket.php?id=$ticket_id");
        }
        
        redirect('view_ticket.php?id=' . $ticket_id . '&success=Ticket updated successfully');
    } else {
        redirect('view_ticket.php?id=' . $ticket_id . '&error=Failed to update ticket');
    }
} else {
    redirect('all_tickets.php');
}
?>
