<?php
require_once '../config.php';

if (!is_logged_in()) {
    redirect('../index.php');
}

if (is_staff()) {
    redirect('../admin/dashboard.php');
}

$user_id = $_SESSION['user_id'];

// Get unread notifications count
$unread_count = get_unread_notifications_count($conn, $user_id);

// Filter by status if provided
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

// Build query
$sql = "SELECT t.*, d.name as department_name, u.full_name as assigned_name
        FROM tickets t 
        LEFT JOIN departments d ON t.department_id = d.id 
        LEFT JOIN users u ON t.assigned_to = u.id
        WHERE t.user_id = ?";

if ($status_filter) {
    $sql .= " AND t.status = ?";
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = $conn->prepare($sql);

if ($status_filter) {
    $stmt->bind_param("is", $user_id, $status_filter);
} else {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$tickets_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tickets - Century Peak Cement</title>
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
        .notification-bell { position: relative; cursor: pointer; padding: 8px; border-radius: 50%; transition: background 0.3s; }
        .notification-bell:hover { background: rgba(255,255,255,0.2); }
        .notification-badge { position: absolute; top: 0; right: 0; background: #f44336; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; border: 2px solid #1a237e; }
        .notification-dropdown { position: absolute; top: 100%; right: 0; background: white; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); min-width: 320px; max-width: 400px; max-height: 400px; overflow-y: auto; display: none; z-index: 1000; }
        .notification-dropdown.show { display: block; }
        .notification-header { padding: 15px 20px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; }
        .notification-header h3 { color: #1a237e; font-size: 16px; margin: 0; }
        .notification-header .mark-read { color: #3949ab; font-size: 13px; cursor: pointer; text-decoration: none; }
        .notification-header .mark-read:hover { text-decoration: underline; }
        .notification-item { padding: 12px 20px; border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: background 0.2s; }
        .notification-item:hover { background: #f8f9fa; }
        .notification-item.unread { background: #e3f2fd; }
        .notification-item.unread:hover { background: #bbdefb; }
        .notification-title { font-weight: 600; color: #333; font-size: 14px; margin-bottom: 4px; }
        .notification-message { color: #666; font-size: 13px; line-height: 1.4; }
        .notification-time { color: #999; font-size: 11px; margin-top: 6px; }
        .notification-empty { padding: 30px; text-align: center; color: #999; }
        .container { max-width: 1200px; margin: 0 auto; padding: 30px; }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .page-header h1 { color: #1a237e; font-size: 28px; }
        .btn {
            padding: 12px 24px;
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
        .filter-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
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
            transition: all 0.3s;
        }
        .filter-btn:hover, .filter-btn.active {
            background: #1a237e;
            color: white;
            border-color: #1a237e;
        }
        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td {
            padding: 16px 12px;
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
        .subject-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .empty-state {
            text-align: center;
            padding: 60px;
            color: #666;
        }
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .stats-bar {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-item {
            background: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #1a237e;
        }
        .stat-label {
            font-size: 13px;
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
            <a href="my_tickets.php" class="active">🎫 My Tickets</a>
            <a href="create_ticket.php">➕ Create Ticket</a>
        </div>
        <div class="navbar-user">
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
                        $notifications = get_notifications($conn, $user_id, 10);
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
            <span><?php echo htmlspecialchars(get_session_full_name()); ?></span>
            <div class="user-avatar"><?php echo strtoupper(substr(get_session_full_name(), 0, 1)); ?></div>
            <a href="../auth/logout.php" class="btn btn-secondary" style="padding: 8px 16px; font-size: 13px;">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1>📋 My Tickets</h1>
            <a href="create_ticket.php" class="btn btn-primary">+ Create New Ticket</a>
        </div>
        
        <div class="filter-bar">
            <a href="my_tickets.php" class="filter-btn <?php echo !$status_filter ? 'active' : ''; ?>">All</a>
            <a href="my_tickets.php?status=open" class="filter-btn <?php echo $status_filter == 'open' ? 'active' : ''; ?>">Open</a>
            <a href="my_tickets.php?status=in_progress" class="filter-btn <?php echo $status_filter == 'in_progress' ? 'active' : ''; ?>">In Progress</a>
            <a href="my_tickets.php?status=resolved" class="filter-btn <?php echo $status_filter == 'resolved' ? 'active' : ''; ?>">Resolved</a>
            <a href="my_tickets.php?status=closed" class="filter-btn <?php echo $status_filter == 'closed' ? 'active' : ''; ?>">Closed</a>
        </div>
        
        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Subject</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Created</th>
                        <th>Last Updated</th>
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
                                <td class="subject-cell" title="<?php echo htmlspecialchars($ticket['subject']); ?>"><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['department_name'] ?? 'General'); ?></td>
                                <td><span class="badge" style="background: <?php echo $status_badge['bg']; ?>; color: <?php echo $status_badge['color']; ?>"><?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?></span></td>
                                <td><span class="badge" style="background: <?php echo $priority_badge['bg']; ?>; color: <?php echo $priority_badge['color']; ?>"><?php echo ucfirst($ticket['priority']); ?></span></td>
                                <td><?php echo date('M j, Y', strtotime($ticket['created_at'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($ticket['updated_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-state-icon">📭</div>
                                    <h3>No tickets found</h3>
                                    <p><?php echo $status_filter ? 'No ' . $status_filter . ' tickets.' : 'You haven\'t created any tickets yet.'; ?></p>
                                    <br>
                                    <a href="create_ticket.php" class="btn btn-primary">Create Your First Ticket</a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        // Notification functions
        function toggleNotifications() { document.getElementById('notificationDropdown').classList.toggle('show'); }
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
        document.addEventListener('click', function(event) { const bell = document.querySelector('.notification-bell'); const dropdown = document.getElementById('notificationDropdown'); if (!bell.contains(event.target) && !dropdown.contains(event.target)) dropdown.classList.remove('show'); });
    </script>
</body>
</html>
