<?php
require_once '../config.php';

if (!is_logged_in() || !is_staff()) {
    redirect('../index.php');
}

$ticket_id = (int)$_GET['id'];

// Get ticket details
$sql = "SELECT t.*, d.name as department_name, u.full_name as creator_name, u.email as creator_email, assignee.full_name as assigned_name
        FROM tickets t 
        LEFT JOIN departments d ON t.department_id = d.id 
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN users assignee ON t.assigned_to = assignee.id
        WHERE t.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();

if (!$ticket) {
    redirect('all_tickets.php?error=Ticket not found');
}

// Get all replies (including internal)
$replies_sql = "SELECT r.*, u.full_name, u.user_type 
                FROM ticket_replies r 
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.ticket_id = ?
                ORDER BY r.created_at ASC";
$stmt = $conn->prepare($replies_sql);
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$replies_result = $stmt->get_result();

// Get ticket activities (events/status changes)
$activities = get_ticket_activities($conn, $ticket_id);

// Get staff for assignment dropdown
$staff_result = $conn->query("SELECT id, full_name, department FROM users WHERE user_type = 'staff' ORDER BY full_name");

// Get departments
$dept_result = $conn->query("SELECT id, name FROM departments ORDER BY name");

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
    <title>Manage Ticket - Century Peak Cement</title>
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
            transition: all 0.3s;
        }
        .navbar-menu a:hover, .navbar-menu a.active {
            background: rgba(255,255,255,0.2);
            color: white;
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
        .container { max-width: 1200px; margin: 0 auto; padding: 30px; }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .page-header h1 { color: #1a237e; font-size: 24px; }
        .admin-badge {
            background: #ff9800;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 700;
        }
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
        .btn-primary {
            background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%);
            color: white;
        }
        .btn-secondary {
            background: white;
            color: #1a237e;
            border: 2px solid #1a237e;
        }
        .btn-success {
            background: #4caf50;
            color: white;
        }
        .btn-danger {
            background: #f44336;
            color: white;
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
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        @media (max-width: 900px) {
            .grid-2 { grid-template-columns: 1fr; }
        }
        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        .card h2 { margin-bottom: 20px; color: #1a237e; font-size: 20px; }
        .card h3 { margin-bottom: 15px; color: #1a237e; font-size: 18px; }
        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .ticket-title h2 {
            color: #1a237e;
            font-size: 22px;
            margin-bottom: 5px;
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
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .info-item label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-bottom: 3px;
        }
        .info-item span {
            font-weight: 600;
            color: #333;
        }
        .description-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .description-box h4 {
            color: #1a237e;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .description-box p {
            line-height: 1.7;
            color: #444;
            white-space: pre-wrap;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-control:focus {
            border-color: #3949ab;
            outline: none;
        }
        select.form-control {
            cursor: pointer;
        }
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        .reply {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 12px;
            border-left: 3px solid #3949ab;
        }
        .reply.staff {
            border-left-color: #4caf50;
            background: #f1f8e9;
        }
        .reply.internal {
            border-left-color: #ff9800;
            background: #fff8e1;
        }
        .reply-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            align-items: center;
        }
        .reply-author {
            font-weight: 700;
            color: #1a237e;
        }
        .reply.staff .reply-author { color: #2e7d32; }
        .reply.internal .reply-author { color: #ef6c00; }
        .reply-time {
            font-size: 12px;
            color: #666;
        }
        .internal-tag {
            background: #ff9800;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            margin-left: 8px;
        }
        .reply-message {
            line-height: 1.6;
            color: #444;
        }
        .activity-timeline {
            position: relative;
            padding-left: 25px;
            max-height: 400px;
            overflow-y: auto;
        }
        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 6px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e0e0e0;
        }
        .activity-item {
            position: relative;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        .activity-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .activity-dot {
            position: absolute;
            left: -22px;
            top: 2px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #3949ab;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #3949ab;
        }
        .activity-dot.status-change { background: #ff9800; box-shadow: 0 0 0 2px #ff9800; }
        .activity-dot.assignment { background: #4caf50; box-shadow: 0 0 0 2px #4caf50; }
        .activity-dot.priority { background: #f44336; box-shadow: 0 0 0 2px #f44336; }
        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        .activity-user {
            font-weight: 600;
            color: #1a237e;
            font-size: 13px;
        }
        .activity-time {
            font-size: 11px;
            color: #888;
        }
        .activity-desc {
            color: #555;
            font-size: 13px;
            line-height: 1.4;
        }
        .activity-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 5px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }
        .checkbox-group input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-weight: 500;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        .no-replies {
            text-align: center;
            padding: 30px;
            color: #666;
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
            <a href="all_tickets.php">🎫 All Tickets</a>
            <a href="departments.php">🏢 Departments</a>
            <a href="staff.php">👥 Users</a>
            <a href="announcements.php">📢 Announcements</a>
        </div>
        <div class="navbar-user">
                        <span><?php echo htmlspecialchars(get_session_full_name('Admin')); ?></span>
            <div class="user-avatar"><?php echo strtoupper(substr(get_session_full_name('Admin'), 0, 1)); ?></div>
            <a href="../auth/logout.php" class="btn btn-secondary" style="padding: 8px 16px; font-size: 13px;">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <div>
                <h1>🎫 Manage Ticket</h1>
                <p style="color: #666;"><?php echo htmlspecialchars($ticket['ticket_number']); ?></p>
            </div>
            <a href="all_tickets.php" class="btn btn-secondary">← Back to All Tickets</a>
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
        
        <div class="grid-2">
            <div>
                <div class="card">
                    <div class="ticket-header">
                        <div class="ticket-title">
                            <h2><?php echo htmlspecialchars($ticket['subject']); ?></h2>
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
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Created By</label>
                            <span><?php echo htmlspecialchars($ticket['creator_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Email</label>
                            <span><?php echo htmlspecialchars($ticket['creator_email']); ?></span>
                        </div>
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
                    
                    <div class="description-box">
                        <h4>📝 Description</h4>
                        <p><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></p>
                    </div>
                </div>
                
                <div class="card">
                    <h3>🛠️ Ticket Actions</h3>
                    
                    <form action="update_ticket.php" method="POST" style="margin-bottom: 20px;">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        
                        <div class="form-group">
                            <label>Assign To Staff</label>
                            <select name="assigned_to" class="form-control">
                                <option value="">-- Not Assigned --</option>
                                <?php while ($staff = $staff_result->fetch_assoc()): ?>
                                    <option value="<?php echo $staff['id']; ?>" <?php echo $ticket['assigned_to'] == $staff['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($staff['full_name'] . ' (' . $staff['department'] . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Update Status</label>
                            <select name="status" class="form-control">
                                <option value="open" <?php echo $ticket['status'] == 'open' ? 'selected' : ''; ?>>Open</option>
                                <option value="in_progress" <?php echo $ticket['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="resolved" <?php echo $ticket['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                <option value="closed" <?php echo $ticket['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Change Priority</label>
                            <select name="priority" class="form-control">
                                <option value="low" <?php echo $ticket['priority'] == 'low' ? 'selected' : ''; ?>>Low</option>
                                <option value="normal" <?php echo $ticket['priority'] == 'normal' ? 'selected' : ''; ?>>Normal</option>
                                <option value="high" <?php echo $ticket['priority'] == 'high' ? 'selected' : ''; ?>>High</option>
                                <option value="urgent" <?php echo $ticket['priority'] == 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">💾 Update Ticket</button>
                    </form>
                    
                    <form action="update_ticket.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this ticket?');">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn btn-danger" style="width: 100%;">🗑️ Delete Ticket</button>
                    </form>
                </div>
            </div>
            
            <div>
                <div class="card">
                    <h3>💬 Conversation</h3>
                    
                    <?php if ($replies_result->num_rows > 0): ?>
                        <?php while ($reply = $replies_result->fetch_assoc()): ?>
                            <div class="reply <?php echo $reply['user_type'] === 'staff' ? 'staff' : ''; ?> <?php echo $reply['is_internal'] ? 'internal' : ''; ?>">
                                <div class="reply-header">
                                    <span class="reply-author">
                                        <?php echo htmlspecialchars($reply['full_name']); ?>
                                        <?php if ($reply['user_type'] === 'staff'): ?>
                                            <span style="font-size: 11px; background: #4caf50; color: white; padding: 2px 8px; border-radius: 10px; margin-left: 5px;">STAFF</span>
                                        <?php endif; ?>
                                        <?php if ($reply['is_internal']): ?>
                                            <span class="internal-tag">INTERNAL</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="reply-time"><?php echo date('M j, Y g:i A', strtotime($reply['created_at'])); ?></span>
                                </div>
                                <div class="reply-message"><?php echo nl2br(htmlspecialchars($reply['message'])); ?></div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-replies">
                            <p>No replies yet</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Ticket Activity Timeline -->
                <div class="card">
                    <h3>📋 Activity Log</h3>
                    <?php if ($activities && $activities->num_rows > 0): ?>
                        <div class="activity-timeline">
                            <?php while ($activity = $activities->fetch_assoc()): 
                                $dot_class = '';
                                if ($activity['activity_type'] == 'status_change') $dot_class = 'status-change';
                                elseif ($activity['activity_type'] == 'assignment') $dot_class = 'assignment';
                                elseif ($activity['activity_type'] == 'priority') $dot_class = 'priority';
                                
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
                        <div style="text-align: center; padding: 20px; color: #888; font-style: italic;">No activity recorded yet</div>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <h3>✏️ Add Reply</h3>
                    <form action="add_reply.php" method="POST">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <div class="form-group">
                            <textarea name="message" class="form-control" placeholder="Type your reply..." required></textarea>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="is_internal" id="is_internal" value="1">
                            <label for="is_internal">Internal note (not visible to employee)</label>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 15px;">📨 Send Reply</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
