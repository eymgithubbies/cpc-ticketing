<?php
require_once '../config.php';

if (!is_logged_in()) {
    redirect('../index.php');
}

if (is_staff()) {
    redirect('../admin/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject = sanitize_input($_POST['subject']);
    $priority = sanitize_input($_POST['priority'] ?? 'normal');
    $description = sanitize_input($_POST['description']);
    $user_id = $_SESSION['user_id'];
    
    // Get user's department from session and find department_id
    $user_department = $_SESSION['department'] ?? '';
    $department_id = null;
    if ($user_department) {
        $dept_stmt = $conn->prepare("SELECT id FROM departments WHERE name = ?");
        $dept_stmt->bind_param("s", $user_department);
        $dept_stmt->execute();
        $dept_result = $dept_stmt->get_result();
        if ($dept_row = $dept_result->fetch_assoc()) {
            $department_id = $dept_row['id'];
        }
    }
    
    // Validation
    if (empty($subject) || empty($description)) {
        redirect('create_ticket.php?error=Please fill in all required fields');
    }
    
    // Generate ticket number
    $ticket_number = generate_ticket_number();
    
    // Insert ticket (category removed, department_id auto-populated)
    $sql = "INSERT INTO tickets (ticket_number, user_id, department_id, subject, priority, description, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'open')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siisss", $ticket_number, $user_id, $department_id, $subject, $priority, $description);
    
    if ($stmt->execute()) {
        $new_ticket_id = $conn->insert_id;

        // Log ticket creation activity
        log_ticket_activity($conn, $new_ticket_id, $user_id, 'created', 'Ticket created by employee', null, 'open');

        // Notify all staff members about new ticket
        $priority_label = ucfirst($priority);
        $notification_title = "New Ticket: $ticket_number";
        $notification_message = "A new ticket has been created with priority: $priority_label";
        $notification_link = "../admin/view_ticket.php?id=$new_ticket_id";
        notify_all_staff($conn, 'new_ticket', $notification_title, $notification_message, $notification_link);

        redirect('dashboard.php?success=Ticket created successfully! Your ticket number is: ' . $ticket_number);
    } else {
        redirect('create_ticket.php?error=Failed to create ticket. Please try again.');
    }
} else {
    redirect('create_ticket.php');
}
?>
