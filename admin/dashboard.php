<?php
require_once '../config.php';

if (!is_logged_in() || !is_staff()) {
    redirect('../index.php');
}

// Get overall statistics (optimized single query)
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed,
    SUM(CASE WHEN priority = 'urgent' AND status IN ('open', 'in_progress') THEN 1 ELSE 0 END) as urgent
    FROM tickets";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Get recent tickets
$tickets_sql = "SELECT t.*, u.full_name as creator_name, d.name as department_name, assignee.full_name as assigned_name
                FROM tickets t 
                LEFT JOIN users u ON t.user_id = u.id 
                LEFT JOIN departments d ON t.department_id = d.id
                LEFT JOIN users assignee ON t.assigned_to = assignee.id
                ORDER BY t.created_at DESC LIMIT 15";
$tickets_result = $conn->query($tickets_sql);

// Get active announcements
$announcements = get_active_announcements($conn, 3);

// Get unread notifications count
$unread_count = get_unread_notifications_count($conn, $_SESSION['user_id']);

// Get current admin's IT status
$current_status = null;
try {
    $current_status_sql = "SELECT * FROM it_status WHERE admin_id = ?";
    $stmt = $conn->prepare($current_status_sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $current_status_result = $stmt->get_result();
    $current_status = $current_status_result->num_rows > 0 ? $current_status_result->fetch_assoc() : null;
} catch (Exception $e) {
    // Table doesn't exist yet, status will be null
    $current_status = null;
}

// Get all open tickets for the ticket dropdown
$open_tickets_sql = "SELECT id, ticket_number, subject FROM tickets WHERE status IN ('open', 'in_progress') ORDER BY created_at DESC LIMIT 50";
$open_tickets = $conn->query($open_tickets_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Century Peak Cement</title>
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

        .mobile-menu-toggle {
            display: none;
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
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
        .notification-bell {
            position: relative;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: background 0.3s;
        }
        .notification-bell:hover {
            background: rgba(255,255,255,0.2);
        }
        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: #f44336;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #1a237e;
        }
        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
            min-width: 320px;
            max-width: 400px;
            max-height: 400px;
            overflow-y: auto;
            display: none;
            z-index: 1000;
        }
        .notification-dropdown.show {
            display: block;
        }
        .notification-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .notification-header h3 {
            color: #1a237e;
            font-size: 16px;
            margin: 0;
        }
        .notification-header .mark-read {
            color: #3949ab;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
        }
        .notification-header .mark-read:hover {
            text-decoration: underline;
        }
        .notification-item {
            padding: 12px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
        }
        .notification-item:hover {
            background: #f8f9fa;
        }
        .notification-item.unread {
            background: #e3f2fd;
        }
        .notification-item.unread:hover {
            background: #bbdefb;
        }
        .notification-title {
            font-weight: 600;
            color: #333;
            font-size: 14px;
            margin-bottom: 4px;
        }
        .notification-message {
            color: #666;
            font-size: 13px;
            line-height: 1.4;
        }
        .notification-time {
            color: #999;
            font-size: 11px;
            margin-top: 6px;
        }
        .notification-empty {
            padding: 30px;
            text-align: center;
            color: #999;
        }
        .container { max-width: 1400px; margin: 0 auto; padding: 30px; }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .page-header h1 { color: #1a237e; font-size: 28px; }
        .admin-badge {
            background: #ff9800;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 700;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #3949ab;
        }
        .stat-card.urgent::before { background: #f44336; }
        .stat-card.open::before { background: #2196f3; }
        .stat-card.in-progress::before { background: #ff9800; }
        .stat-card.resolved::before { background: #4caf50; }
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #1a237e;
            margin-bottom: 5px;
        }
        .stat-card.urgent .stat-number { color: #f44336; }
        .stat-label { color: #666; font-size: 14px; }
        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        .card h2 { margin-bottom: 20px; color: #1a237e; font-size: 20px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .table th { color: #666; font-weight: 600; font-size: 13px; text-transform: uppercase; }
        .table tr:hover { background: #f8f9fa; }
        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .ticket-link {
            color: #3949ab;
            text-decoration: none;
            font-weight: 600;
        }
        .ticket-link:hover { text-decoration: underline; }
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
        .dept-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        .dept-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .dept-name { font-weight: 600; color: #333; }
        .dept-count {
            background: #3949ab;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 700;
        }
        .filter-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .filter-btn {
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            background: white;
            color: #666;
            border: 1px solid #e0e0e0;
        }
        .filter-btn:hover {
            background: #1a237e;
            color: white;
            border-color: #1a237e;
        }
        .status-form-group {
            margin-bottom: 15px;
        }
        .status-form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
            font-size: 13px;
        }
        .status-form-control {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            outline: none;
        }
        .status-form-control:focus {
            border-color: #3949ab;
            box-shadow: 0 0 0 3px rgba(57, 73, 171, 0.1);
        }
        .status-success {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 13px;
            display: none;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }

            .navbar-menu {
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }

            .navbar-menu a {
                font-size: 12px;
                padding: 6px 12px;
            }

            .navbar-user {
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }

            .container {
                padding: 15px;
            }

            .page-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .page-header h1 {
                font-size: 22px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .stat-card {
                padding: 18px;
            }

            .stat-number {
                font-size: 28px;
            }

            .card {
                padding: 18px;
            }

            .card h2 {
                font-size: 18px;
            }

            .table {
                font-size: 12px;
            }

            .table th, .table td {
                padding: 10px 8px;
            }

            .notification-dropdown {
                min-width: 280px;
                max-width: 300px;
                right: -100px;
            }

            .btn {
                padding: 10px 16px;
                font-size: 13px;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .navbar-menu {
                display: none;
                width: 100%;
                flex-direction: column;
                align-items: center;
            }

            .navbar-menu.show {
                display: flex;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .navbar-brand {
                font-size: 16px;
            }

            .navbar-brand img {
                width: 28px;
                height: 28px;
            }

            .navbar-menu {
                gap: 8px;
            }

            .navbar-menu a {
                font-size: 11px;
                padding: 5px 10px;
            }

            .page-header h1 {
                font-size: 18px;
            }

            .stat-number {
                font-size: 24px;
            }

            .stat-label {
                font-size: 12px;
            }

            .card h2 {
                font-size: 16px;
            }

            .status-form-control {
                font-size: 14px;
            }

            /* Make table scrollable on mobile */
            .card {
                overflow-x: auto;
            }

            .table {
                min-width: 600px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <img src="../Logi%20image/cpc.png" alt="CPC" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
            <span>IT HELPDESK</span>
        </div>
        <div class="mobile-menu-toggle" onclick="toggleMobileMenu()">☰</div>
        <div class="navbar-menu" id="navbarMenu">
            <a href="dashboard.php" class="active">📊 Dashboard</a>
            <a href="all_tickets.php">🎫 All Tickets</a>
            <a href="departments.php">🏢 Departments</a>
            <a href="staff.php">👥 Users</a>
            <a href="announcements.php">📢 Announcements</a>
        </div>
        <div class="navbar-user">
                        <!-- Notification Bell -->
            <div class="notification-bell" onclick="toggleNotifications()">
                <span style="font-size: 22px;">🔔</span>
                <?php if ($unread_count > 0): ?>
                    <span class="notification-badge"><?php echo $unread_count > 9 ? '9+' : $unread_count; ?></span>
                <?php endif; ?>
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <h3>Notifications</h3>
                        <a href="#" class="mark-read" onclick="markAllRead(event); return false;">Mark all read</a>
                    </div>
                    <div id="notificationList">
                        <?php
                        $notifications = get_notifications($conn, $_SESSION['user_id'], 10);
                        if ($notifications->num_rows > 0):
                            while ($notif = $notifications->fetch_assoc()):
                        ?>
                            <div class="notification-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?>"
                                 onclick="viewNotification(<?php echo $notif['id']; ?>, '<?php echo htmlspecialchars($notif['link'] ?? '#'); ?>')">
                                <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                                <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                                <div class="notification-time"><?php echo time_ago($notif['created_at']); ?></div>
                            </div>
                        <?php endwhile; else: ?>
                            <div class="notification-empty">No notifications</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <span><?php echo htmlspecialchars(get_session_full_name('Admin')); ?></span>
            <div class="user-avatar"><?php echo strtoupper(substr(get_session_full_name('Admin'), 0, 1)); ?></div>
            <a href="../auth/logout.php" class="btn btn-secondary" style="padding: 8px 16px; font-size: 13px;">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1>📊 Admin Dashboard</h1>
            <a href="all_tickets.php" class="btn btn-primary">View All Tickets</a>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="stat-label">Total Tickets</div>
            </div>
            <div class="stat-card urgent">
                <div class="stat-number"><?php echo $stats['urgent'] ?? 0; ?></div>
                <div class="stat-label">Urgent (Open)</div>
            </div>
            <div class="stat-card open">
                <div class="stat-number"><?php echo $stats['open'] ?? 0; ?></div>
                <div class="stat-label">Open</div>
            </div>
            <div class="stat-card in-progress">
                <div class="stat-number"><?php echo $stats['in_progress'] ?? 0; ?></div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat-card resolved">
                <div class="stat-number"><?php echo $stats['resolved'] ?? 0; ?></div>
                <div class="stat-label">Resolved</div>
            </div>
            <div class="stat-card" style="border-left-color: #9e9e9e;">
                <div class="stat-number" style="color: #9e9e9e;"><?php echo $stats['closed'] ?? 0; ?></div>
                <div class="stat-label">Closed</div>
            </div>
        </div>
        
        <?php if ($announcements->num_rows > 0): ?>
        <div class="card" style="margin-bottom: 25px;">
            <h2>📢 Announcements</h2>
            <div style="display: grid; gap: 15px;">
                <?php while ($ann = $announcements->fetch_assoc()): 
                    $priority_colors = [
                        'high' => ['border' => '#f44336', 'bg' => '#ffebee'],
                        'normal' => ['border' => '#2196f3', 'bg' => '#e3f2fd'],
                        'low' => ['border' => '#4caf50', 'bg' => '#e8f5e8']
                    ];
                    $colors = $priority_colors[$ann['priority']] ?? $priority_colors['normal'];
                ?>
                    <div style="padding: 15px 20px; background: <?php echo $colors['bg']; ?>; border-left: 4px solid <?php echo $colors['border']; ?>; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <strong style="color: #333; font-size: 15px;"><?php echo htmlspecialchars($ann['title']); ?></strong>
                            <span style="font-size: 11px; color: #666;"><?php echo date('M j, Y', strtotime($ann['created_at'])); ?></span>
                        </div>
                        <p style="color: #555; font-size: 14px; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($ann['content'])); ?></p>
                    </div>
                <?php endwhile; ?>
            </div>
            <div style="text-align: right; margin-top: 15px;">
                <a href="announcements.php" style="color: #3949ab; text-decoration: none; font-size: 13px; font-weight: 600;">Manage Announcements →</a>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>🔄 Update My Status</h2>
            <div id="statusSuccess" class="status-success">✓ Status updated successfully!</div>
            <form id="statusForm">
                <div class="status-form-group">
                    <label>Current Activity</label>
                    <input type="text" name="current_activity" class="status-form-control" 
                           placeholder="e.g., Repairing System Unit - LAB CCR" 
                           value="<?php echo htmlspecialchars($current_status['current_activity'] ?? ''); ?>">
                </div>
                <div class="status-form-group">
                    <label>Status</label>
                    <select name="status" class="status-form-control">
                        <option value="Available" <?php echo ($current_status['status'] ?? '') === 'Available' ? 'selected' : ''; ?>>🟢 Available</option>
                        <option value="Busy" <?php echo ($current_status['status'] ?? '') === 'Busy' ? 'selected' : ''; ?>>🟡 Busy</option>
                        <option value="On-site" <?php echo ($current_status['status'] ?? '') === 'On-site' ? 'selected' : ''; ?>>🔵 On-site</option>
                        <option value="In Progress" <?php echo ($current_status['status'] ?? '') === 'In Progress' ? 'selected' : ''; ?>>🟠 In Progress</option>
                        <option value="Break" <?php echo ($current_status['status'] ?? '') === 'Break' ? 'selected' : ''; ?>>⚪ Break</option>
                    </select>
                </div>
                <div class="status-form-group">
                    <label>Current Location</label>
                    <input type="text" name="location" class="status-form-control" 
                           placeholder="e.g., LAB CCR" 
                           value="<?php echo htmlspecialchars($current_status['location'] ?? ''); ?>">
                </div>
                <div class="status-form-group">
                    <label>Related Ticket (Optional)</label>
                    <select name="related_ticket_id" class="status-form-control">
                        <option value="">-- Select Ticket --</option>
                        <?php while ($ticket = $open_tickets->fetch_assoc()): ?>
                            <option value="<?php echo $ticket['id']; ?>" 
                                    <?php echo ($current_status['related_ticket_id'] ?? '') == $ticket['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ticket['ticket_number'] . ' - ' . substr($ticket['subject'], 0, 30)); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">💾 Save Status</button>
            </form>
        </div>
        
        <div class="card">
            <h2>🎫 Recent Tickets</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Subject</th>
                        <th>Created By</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Priority</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($tickets_result->num_rows > 0): ?>
                        <?php while ($ticket = $tickets_result->fetch_assoc()): 
                            $status_badge = get_status_badge($ticket['status']);
                            $priority_badge = get_priority_badge($ticket['priority']);
                        ?>
                            <tr>
                                <td><a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="ticket-link"><?php echo htmlspecialchars($ticket['ticket_number']); ?></a></td>
                                <td><?php echo htmlspecialchars(substr($ticket['subject'], 0, 30)) . (strlen($ticket['subject']) > 30 ? '...' : ''); ?></td>
                                <td><?php echo htmlspecialchars($ticket['creator_name']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['department_name'] ?? 'General'); ?></td>
                                <td><span class="badge" style="background: <?php echo $status_badge['bg']; ?>; color: <?php echo $status_badge['color']; ?>"><?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?></span></td>
                                <td><span class="badge" style="background: <?php echo $priority_badge['bg']; ?>; color: <?php echo $priority_badge['color']; ?>"><?php echo ucfirst($ticket['priority']); ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; padding: 40px; color: #666;">No tickets found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('navbarMenu');
            menu.classList.toggle('show');
        }

        function toggleNotifications() {
            const dropdown = document.getElementById('notificationDropdown');
            dropdown.classList.toggle('show');
        }

        function viewNotification(id, link) {
            // Mark as read via AJAX
            fetch('mark_notification_read.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Navigate to the link
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
                        location.reload();
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

        // Status form submission
        document.getElementById('statusForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('save_it_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Server returned ' + response.status);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Response text:', text);
                        throw new Error('Invalid JSON response');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    const successMsg = document.getElementById('statusSuccess');
                    successMsg.style.display = 'block';
                    setTimeout(() => {
                        successMsg.style.display = 'none';
                    }, 3000);
                } else {
                    alert('Error saving status: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Error saving status: ' + error);
            });
        });
    </script>
</body>
</html>
