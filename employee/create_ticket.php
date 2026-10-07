<?php
require_once '../config.php';

if (!is_logged_in()) {
    redirect('../index.php');
}

if (is_staff()) {
    redirect('../admin/dashboard.php');
}

// Get unread notifications count
$unread_count = get_unread_notifications_count($conn, $_SESSION['user_id']);

$error = '';
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Ticket - Century Peak Cement</title>
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
        .container { max-width: 800px; margin: 0 auto; padding: 30px; }
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
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        .card {
            background: white;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        .form-group label .required {
            color: #c62828;
        }
        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            outline: none;
            font-family: inherit;
        }
        .form-control:focus {
            border-color: #3949ab;
            box-shadow: 0 0 0 4px rgba(57, 73, 171, 0.1);
        }
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        select.form-control {
            cursor: pointer;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
        }
        .info-box {
            background: #e3f2fd;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #1565c0;
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
            <a href="create_ticket.php" class="active">➕ Create Ticket</a>
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
            <span><?php echo htmlspecialchars(get_session_full_name()); ?></span>
            <div class="user-avatar"><?php echo strtoupper(substr(get_session_full_name(), 0, 1)); ?></div>
            <a href="../auth/logout.php" class="btn btn-secondary" style="padding: 8px 16px; font-size: 13px;">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1>🎫 Create New Ticket</h1>
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <span>⚠️</span>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="info-box">
                <span>ℹ️</span>
                <span>Please provide detailed information about your issue so we can assist you better.</span>
            </div>
            
            <form action="submit_ticket.php" method="POST">
                <div class="form-group">
                    <label>Ticket Subject <span class="required">*</span></label>
                    <input type="text" name="subject" class="form-control" 
                           placeholder="Brief description of your issue" required>
                </div>
                
                
                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority" class="form-control">
                        <option value="low">Low - Not urgent</option>
                        <option value="normal" selected>Normal - Standard request</option>
                        <option value="high">High - Affects work</option>
                        <option value="urgent">Urgent - Critical issue</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Detailed Description <span class="required">*</span></label>
                    <textarea name="description" class="form-control" 
                              placeholder="Please provide as much detail as possible about your issue..." required></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 16px;">
                    📤 Submit Ticket
                </button>
            </form>
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
    </script>
</body>
</html>
