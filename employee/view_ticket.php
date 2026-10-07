<?php
require_once '../config.php';

if (!is_logged_in()) {
    redirect('../index.php');
}

if (is_staff()) {
    redirect('../admin/dashboard.php');
}

$user_id = $_SESSION['user_id'];
$ticket_id = (int)$_GET['id'];

// Get ticket details
$sql = "SELECT t.*, d.name as department_name, u.full_name as assigned_name, creator.full_name as creator_name
        FROM tickets t 
        LEFT JOIN departments d ON t.department_id = d.id 
        LEFT JOIN users u ON t.assigned_to = u.id
        LEFT JOIN users creator ON t.user_id = creator.id
        WHERE t.id = ? AND t.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $ticket_id, $user_id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();

if (!$ticket) {
    redirect('my_tickets.php?error=Ticket not found');
}

// Get ticket replies
$replies_sql = "SELECT r.*, u.full_name, u.user_type 
                FROM ticket_replies r 
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.ticket_id = ? AND r.is_internal = 0
                ORDER BY r.created_at ASC";
$stmt = $conn->prepare($replies_sql);
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$replies_result = $stmt->get_result();

// Get ticket activities (events/status changes)
$activities = get_ticket_activities($conn, $ticket_id);

$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

$status_badge = get_status_badge($ticket['status']);
$priority_badge = get_priority_badge($ticket['priority']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Ticket - Century Peak Cement</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }
        .navbar {
            background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: 700;
        }
        .navbar-menu { display: flex; gap: 30px; }
        .navbar-menu a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .navbar-menu a:hover, .navbar-menu a.active {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .navbar-menu a:active {
            transform: translateY(0) scale(0.95);
        }
        .navbar-menu a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background: #00ff41;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateX(-50%);
            border-radius: 2px;
        }
        .navbar-menu a:hover::after, .navbar-menu a.active::after {
            width: 80%;
        }
        .navbar-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: white;
            color: #1a237e;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        .container { max-width: 1000px; margin: 0 auto; padding: 30px; }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .page-header h1 { color: #1a237e; font-size: 24px; }
        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }
        .btn-secondary {
            background: white;
            color: #1a237e;
            border: 2px solid #1a237e;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .alert-success {
            background: #e8f5e8;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        .ticket-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .ticket-title h2 {
            color: #1a237e;
            font-size: 22px;
            margin-bottom: 10px;
        }
        .ticket-meta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .ticket-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .info-item label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .info-item span {
            font-weight: 600;
            color: #333;
        }
        .ticket-description {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        .ticket-description h3 {
            color: #1a237e;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .ticket-description p {
            line-height: 1.7;
            color: #444;
            white-space: pre-wrap;
        }
        .replies-section h3 {
            color: #1a237e;
            margin-bottom: 20px;
            font-size: 18px;
        }
        .reply {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            border-left: 4px solid #3949ab;
        }
        .reply.staff {
            border-left-color: #4caf50;
            background: #f1f8e9;
        }
        .reply-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .reply-author {
            font-weight: 700;
            color: #1a237e;
        }
        .reply.staff .reply-author {
            color: #2e7d32;
        }
        .reply-time {
            font-size: 13px;
            color: #666;
        }
        .reply-message {
            line-height: 1.6;
            color: #444;
        }
        .reply-form {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-top: 25px;
        }
        .reply-form h3 {
            color: #1a237e;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #333;
            font-weight: 600;
        }
        textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }
        textarea:focus {
            border-color: #3949ab;
            outline: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%);
            color: white;
            padding: 12px 24px;
        }
        .no-replies {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .activity-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        .activity-section h3 {
            color: #1a237e;
            margin-bottom: 20px;
            font-size: 18px;
        }
        .activity-timeline {
            position: relative;
            padding-left: 30px;
        }
        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e0e0e0;
        }
        .activity-item {
            position: relative;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        .activity-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .activity-dot {
            position: absolute;
            left: -26px;
            top: 2px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #3949ab;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #3949ab;
        }
        .activity-dot.status-change { background: #ff9800; box-shadow: 0 0 0 2px #ff9800; }
        .activity-dot.assignment { background: #4caf50; box-shadow: 0 0 0 2px #4caf50; }
        .activity-dot.priority { background: #f44336; box-shadow: 0 0 0 2px #f44336; }
        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .activity-user {
            font-weight: 600;
            color: #1a237e;
            font-size: 14px;
        }
        .activity-time {
            font-size: 12px;
            color: #888;
        }
        .activity-desc {
            color: #555;
            font-size: 14px;
            line-height: 1.5;
        }
        .activity-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
        }
        .no-activities {
            text-align: center;
            padding: 30px;
            color: #888;
            font-style: italic;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <img src="../Logi%20image/cpc.png" alt="CPC" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
            <span>IT HELPDESK</span>
        </div>
        <div class="navbar-menu">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="my_tickets.php">🎫 My Tickets</a>
            <a href="create_ticket.php">➕ Create Ticket</a>
        </div>
        <div class="navbar-user">
            <span><?php echo htmlspecialchars(get_session_full_name()); ?></span>
            <div class="user-avatar"><?php echo strtoupper(substr(get_session_full_name(), 0, 1)); ?></div>
            <a href="../auth/logout.php" class="btn btn-secondary" style="padding: 8px 16px; font-size: 13px;">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1>🎫 Ticket Details</h1>
            <a href="my_tickets.php" class="btn btn-secondary">← Back to My Tickets</a>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <span>✓</span> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <span>⚠️</span> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="ticket-card">
            <div class="ticket-header">
                <div class="ticket-title">
                    <h2><?php echo htmlspecialchars($ticket['subject']); ?></h2>
                    <p style="color: #666; font-size: 14px;"><?php echo htmlspecialchars($ticket['ticket_number']); ?></p>
                </div>
                <div class="ticket-meta">
                    <span class="badge" style="background: <?php echo $status_badge['bg']; ?>; color: <?php echo $status_badge['color']; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                    </span>
                    <span class="badge" style="background: <?php echo $priority_badge['bg']; ?>; color: <?php echo $priority_badge['color']; ?>">
                        <?php echo ucfirst($ticket['priority']); ?>
                    </span>
                </div>
            </div>
            
            <div class="ticket-info-grid">
                <div class="info-item">
                    <label>Department</label>
                    <span><?php echo htmlspecialchars($ticket['department_name'] ?? 'General'); ?></span>
                </div>
                <div class="info-item">
                    <label>Category</label>
                    <span><?php echo htmlspecialchars($ticket['category'] ?? 'General'); ?></span>
                </div>
                <div class="info-item">
                    <label>Assigned To</label>
                    <span><?php echo htmlspecialchars($ticket['assigned_name'] ?? 'Not Assigned'); ?></span>
                </div>
                <div class="info-item">
                    <label>Created</label>
                    <span><?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?></span>
                </div>
            </div>
            
            <div class="ticket-description">
                <h3>📝 Description</h3>
                <p><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></p>
            </div>
        </div>
        
        <!-- Ticket Activity Timeline -->
        <div class="activity-section">
            <h3>📋 Ticket Activity Log</h3>
            <?php if ($activities && $activities->num_rows > 0): ?>
                <div class="activity-timeline">
                    <?php while ($activity = $activities->fetch_assoc()): 
                        $dot_class = '';
                        if ($activity['activity_type'] == 'status_change') $dot_class = 'status-change';
                        elseif ($activity['activity_type'] == 'assignment') $dot_class = 'assignment';
                        elseif ($activity['activity_type'] == 'priority_change') $dot_class = 'priority';
                        
                        $user_type_badge = $activity['user_type'] == 'staff' ? '<span class="activity-badge" style="background: #ff9800; color: white;">ADMIN</span>' : '<span class="activity-badge" style="background: #2196f3; color: white;">USER</span>';
                    ?>
                        <div class="activity-item">
                            <div class="activity-dot <?php echo $dot_class; ?>"></div>
                            <div class="activity-header">
                                <span class="activity-user">
                                    <?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?>
                                    <?php echo $user_type_badge; ?>
                                </span>
                                <span class="activity-time"><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></span>
                            </div>
                            <div class="activity-desc">
                                <?php echo htmlspecialchars($activity['description']); ?>
                                <?php if ($activity['old_value'] && $activity['new_value']): ?>
                                    <br><small style="color: #888;">
                                        <?php if ($activity['activity_type'] == 'status_change'): ?>
                                            <span style="text-decoration: line-through;"><?php echo ucfirst(str_replace('_', ' ', $activity['old_value'])); ?></span> 
                                            → <strong><?php echo ucfirst(str_replace('_', ' ', $activity['new_value'])); ?></strong>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($activity['old_value']); ?> → <?php echo htmlspecialchars($activity['new_value']); ?>
                                        <?php endif; ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-activities">No activity recorded yet</div>
            <?php endif; ?>
        </div>
        
        <div class="replies-section">
            <h3>💬 Conversation History</h3>
            
            <?php if ($replies_result->num_rows > 0): ?>
                <?php while ($reply = $replies_result->fetch_assoc()): ?>
                    <div class="reply <?php echo $reply['user_type'] === 'staff' ? 'staff' : ''; ?>">
                        <div class="reply-header">
                            <span class="reply-author">
                                <?php echo htmlspecialchars($reply['full_name']); ?>
                                <?php if ($reply['user_type'] === 'staff'): ?>
                                    <span style="font-size: 11px; background: #4caf50; color: white; padding: 2px 8px; border-radius: 10px; margin-left: 8px;">STAFF</span>
                                <?php endif; ?>
                            </span>
                            <span class="reply-time"><?php echo date('M j, Y g:i A', strtotime($reply['created_at'])); ?></span>
                        </div>
                        <div class="reply-message"><?php echo nl2br(htmlspecialchars($reply['message'])); ?></div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-replies">
                    <p>No replies yet. Use the form below to add a follow-up.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($ticket['status'] !== 'closed'): ?>
            <div class="reply-form">
                <h3>✏️ Add Follow-up</h3>
                <form action="add_reply.php" method="POST">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <div class="form-group">
                        <label>Your Message</label>
                        <textarea name="message" placeholder="Type your message here..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
            </div>
        <?php else: ?>
            <div class="ticket-card" style="text-align: center; color: #666;">
                <p>🔒 This ticket has been closed. You cannot add new replies.</p>
            </div>
        <?php endif; ?>
    </div>
    <script>
        // Notification functions
        function toggleNotifications() {
            const dropdown = document.getElementById('notificationDropdown');
            dropdown.classList.toggle('show');
        }

        function viewNotification(id, link) {
            fetch('mark_notification_read.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update badge count
                        const badge = document.querySelector('.notification-badge');
                        if (badge) {
                            const currentCount = parseInt(badge.textContent);
                            if (currentCount > 1) {
                                badge.textContent = currentCount - 1 > 9 ? '9+' : currentCount - 1;
                            } else {
                                badge.style.display = 'none';
                            }
                        }
                        // Navigate to link
                        if (link && link !== '#') {
                            window.location.href = link;
                        }
                    }
                });
        }

        function markAllRead(event) {
            event.stopPropagation();
            fetch('mark_all_notifications_read.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Hide badge immediately
                        const badge = document.querySelector('.notification-badge');
                        if (badge) {
                            badge.style.display = 'none';
                        }
                        // Mark all notification items as read visually
                        document.querySelectorAll('.notification-item.unread').forEach(item => {
                            item.classList.remove('unread');
                        });
                    }
                });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const bell = document.querySelector('.notification-bell');
            const dropdown = document.getElementById('notificationDropdown');
            if (!bell.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });
    </script>
</body>
</html>
