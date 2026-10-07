<?php
require_once '../config.php';

if (!is_logged_in()) {
    redirect('../index.php');
}

if (is_staff()) {
    redirect('../admin/dashboard.php');
}

$user_id = $_SESSION['user_id'];

// Get employee's ticket statistics (optimized)
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed
    FROM tickets WHERE user_id = ?";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Get recent tickets
$tickets_sql = "SELECT t.*, d.name as department_name, u.full_name as assigned_name
                FROM tickets t 
                LEFT JOIN departments d ON t.department_id = d.id 
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.user_id = ? 
                ORDER BY t.created_at DESC LIMIT 10";
$stmt = $conn->prepare($tickets_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tickets_result = $stmt->get_result();


// Get active announcements
$announcements = get_active_announcements($conn, 3);

// Get unread notifications count
$unread_count = get_unread_notifications_count($conn, $user_id);

$success = isset($_GET['success']) ? $_GET['success'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Century Peak Cement</title>
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
        .container { max-width: 1200px; margin: 0 auto; padding: 30px; }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
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
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(26, 35, 126, 0.3); }
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-left: 4px solid #3949ab;
        }
        .stat-card.resolved { border-left-color: #4caf50; }
        .stat-card.in-progress { border-left-color: #ff9800; }
        .stat-card.closed { border-left-color: #9e9e9e; }
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #1a237e;
            margin-bottom: 5px;
        }
        .stat-label { color: #666; font-size: 14px; }
        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
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
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
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
        .it-status-card {
            background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
        }
        .it-status-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 700;
        }
        .it-status-content {
            display: grid;
            gap: 15px;
        }
        .it-status-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
        }
        .it-status-label {
            font-size: 13px;
            opacity: 0.9;
        }
        .it-status-value {
            font-weight: 600;
            font-size: 14px;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        .status-badge.available {
            background: #e8f5e8;
            color: #2e7d32;
        }
        .status-badge.busy {
            background: #fff9c4;
            color: #f57f17;
        }
        .status-badge.on-site {
            background: #e3f2fd;
            color: #1565c0;
        }
        .status-badge.in-progress {
            background: #ffe0b2;
            color: #e65100;
        }
        .status-badge.break {
            background: #f5f5f5;
            color: #616161;
        }
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        .status-dot.green { background: #4caf50; }
        .status-dot.yellow { background: #ffeb3b; }
        .status-dot.blue { background: #2196f3; }
        .status-dot.orange { background: #ff9800; }
        .status-dot.gray { background: #9e9e9e; }
        .it-status-loading {
            text-align: center;
            padding: 20px;
            color: rgba(255,255,255,0.7);
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
            <a href="dashboard.php" class="active">📊 Dashboard</a>
            <a href="my_tickets.php">🎫 My Tickets</a>
            <a href="create_ticket.php">➕ Create Ticket</a>
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
        <?php if ($success): ?>
            <div class="alert alert-success">
                <span>✓</span>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h1>Welcome, <?php echo htmlspecialchars(get_session_full_name()); ?></h1>
            <a href="create_ticket.php" class="btn btn-primary">+ Create New Ticket</a>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="stat-label">Total Tickets</div>
            </div>
            <div class="stat-card" style="border-left-color: #2196f3;">
                <div class="stat-number" style="color: #2196f3;"><?php echo $stats['open'] ?? 0; ?></div>
                <div class="stat-label">Open</div>
            </div>
            <div class="stat-card in-progress">
                <div class="stat-number" style="color: #ff9800;"><?php echo $stats['in_progress'] ?? 0; ?></div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat-card resolved">
                <div class="stat-number" style="color: #4caf50;"><?php echo $stats['resolved'] ?? 0; ?></div>
                <div class="stat-label">Resolved</div>
            </div>
            <div class="stat-card closed">
                <div class="stat-number" style="color: #9e9e9e;"><?php echo $stats['closed'] ?? 0; ?></div>
                <div class="stat-label">Closed</div>
            </div>
        </div>
        
        <div class="it-status-card">
            <div class="it-status-header">
                👨‍💻 IT Current Status
            </div>
            <div id="itStatusContent" class="it-status-content">
                <div class="it-status-loading">Loading IT status...</div>
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
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>📋 My Recent Tickets</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Subject</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Created</th>
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
                                <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['assigned_name'] ?? 'Not Assigned'); ?></td>
                                <td><span class="badge" style="background: <?php echo $status_badge['bg']; ?>; color: <?php echo $status_badge['color']; ?>"><?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?></span></td>
                                <td><span class="badge" style="background: <?php echo $priority_badge['bg']; ?>; color: <?php echo $priority_badge['color']; ?>"><?php echo ucfirst($ticket['priority']); ?></span></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-state-icon">🎫</div>
                                    <p>No tickets yet. <a href="create_ticket.php" class="ticket-link">Create your first ticket</a></p>
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

        // IT Status functions
        function getStatusDotColor(status) {
            const colors = {
                'Available': 'green',
                'Busy': 'yellow',
                'On-site': 'blue',
                'In Progress': 'orange',
                'Break': 'gray'
            };
            return colors[status] || 'gray';
        }

        function getStatusBadgeClass(status) {
            const classes = {
                'Available': 'available',
                'Busy': 'busy',
                'On-site': 'on-site',
                'In Progress': 'in-progress',
                'Break': 'break'
            };
            return classes[status] || 'break';
        }

        function formatTime(timeStr) {
            if (!timeStr) return 'Not set';
            const [hours, minutes] = timeStr.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const formattedHour = hour % 12 || 12;
            return `${formattedHour}:${minutes} ${ampm}`;
        }

        function timeAgo(datetime) {
            const time = new Date(datetime).getTime();
            const now = Date.now();
            const diff = Math.floor((now - time) / 1000);

            if (diff < 60) return 'Just now';
            if (diff < 3600) {
                const mins = Math.floor(diff / 60);
                return mins === 1 ? '1 minute ago' : `${mins} minutes ago`;
            }
            if (diff < 86400) {
                const hours = Math.floor(diff / 3600);
                return hours === 1 ? '1 hour ago' : `${hours} hours ago`;
            }
            return 'More than a day ago';
        }

        function loadITStatus() {
            fetch('../admin/get_it_status.php')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('itStatusContent');
                    if (data.success && data.data) {
                        const status = data.data;
                        const dotColor = getStatusDotColor(status.status);
                        const badgeClass = getStatusBadgeClass(status.status);
                        
                        container.innerHTML = `
                            <div class="it-status-row">
                                <span class="it-status-label">Current Activity</span>
                                <span class="it-status-value">${status.current_activity || 'Not set'}</span>
                            </div>
                            <div class="it-status-row">
                                <span class="it-status-label">Status</span>
                                <span class="status-badge ${badgeClass}">
                                    <span class="status-dot ${dotColor}"></span>
                                    ${status.status}
                                </span>
                            </div>
                            <div class="it-status-row">
                                <span class="it-status-label">Location</span>
                                <span class="it-status-value">${status.location || 'Not set'}</span>
                            </div>
                            <div class="it-status-row">
                                <span class="it-status-label">Last Updated</span>
                                <span class="it-status-value">${timeAgo(status.updated_at)}</span>
                            </div>
                        `;
                    } else {
                        container.innerHTML = '<div class="it-status-loading">No IT status available</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading IT status:', error);
                    document.getElementById('itStatusContent').innerHTML = '<div class="it-status-loading">Error loading status</div>';
                });
        }

        // Load IT status on page load
        loadITStatus();

        // Auto-refresh every 30 seconds
        setInterval(loadITStatus, 30000);
    </script>
</body>
</html>
