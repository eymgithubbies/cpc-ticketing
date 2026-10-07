<?php
require_once '../config.php';

if (!is_logged_in() || !is_staff()) {
    redirect('../index.php');
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    redirect('announcements.php?success=Announcement deleted');
}

// Handle toggle active status
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE announcements SET is_active = NOT is_active WHERE id = $id");
    redirect('announcements.php?success=Status updated');
}

// Get all announcements
$announcements = $conn->query("SELECT a.*, u.full_name as creator_name 
    FROM announcements a 
    LEFT JOIN users u ON a.created_by = u.id 
    ORDER BY a.created_at DESC");

$success = isset($_GET['success']) ? $_GET['success'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Century Peak Cement</title>
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
        .btn-danger {
            background: #f44336;
            color: white;
        }
        .btn-success {
            background: #4caf50;
            color: white;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            background: #e8f5e8;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        .card h2 { margin-bottom: 20px; color: #1a237e; }
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
        .badge-high { background: #ffebee; color: #c62828; }
        .badge-normal { background: #e3f2fd; color: #1565c0; }
        .badge-low { background: #e8f5e8; color: #2e7d32; }
        .status-active { background: #4caf50; color: white; }
        .status-inactive { background: #9e9e9e; color: white; }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 12px 15px;
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
            min-height: 100px;
            resize: vertical;
        }
        select.form-control { cursor: pointer; }
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 6px;
        }
        .content-preview {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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
            <a href="announcements.php" class="active">📢 Announcements</a>
        </div>
        <div class="navbar-user">
                        <span><?php echo htmlspecialchars(get_session_full_name('Admin')); ?></span>
            <div class="user-avatar"><?php echo strtoupper(substr(get_session_full_name('Admin'), 0, 1)); ?></div>
            <a href="../auth/logout.php" class="btn btn-secondary" style="padding: 8px 16px; font-size: 13px;">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1>📢 Announcements</h1>
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
        
        <?php if ($success): ?>
            <div class="alert">
                <span>✓</span> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <!-- Create New Announcement -->
        <div class="card">
            <h2>Create New Announcement</h2>
            <form action="save_announcement.php" method="POST">
                <div class="form-group">
                    <label>Title <span style="color: #c62828;">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="Enter announcement title" required>
                </div>
                <div class="form-group">
                    <label>Content <span style="color: #c62828;">*</span></label>
                    <textarea name="content" class="form-control" placeholder="Enter announcement content..." required></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Priority</label>
                        <select name="priority" class="form-control">
                            <option value="low">Low - General Info</option>
                            <option value="normal" selected>Normal - Important</option>
                            <option value="high">High - Urgent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Expires At (Optional)</label>
                        <input type="datetime-local" name="expires_at" class="form-control">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
                    📢 Post Announcement
                </button>
            </form>
        </div>
        
        <!-- Existing Announcements -->
        <div class="card">
            <h2>All Announcements</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Content</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($announcements->num_rows > 0): ?>
                        <?php while ($ann = $announcements->fetch_assoc()): 
                            $priority_class = 'badge-' . $ann['priority'];
                            $status_class = $ann['is_active'] ? 'status-active' : 'status-inactive';
                            $status_text = $ann['is_active'] ? 'Active' : 'Inactive';
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($ann['title']); ?></strong></td>
                                <td><div class="content-preview"><?php echo htmlspecialchars($ann['content']); ?></div></td>
                                <td><span class="badge <?php echo $priority_class; ?>"><?php echo ucfirst($ann['priority']); ?></span></td>
                                <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                <td><?php echo htmlspecialchars($ann['creator_name']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($ann['created_at'])); ?></td>
                                <td>
                                    <a href="?toggle=<?php echo $ann['id']; ?>" class="btn btn-success btn-small" style="text-decoration: none;">
                                        <?php echo $ann['is_active'] ? 'Hide' : 'Show'; ?>
                                    </a>
                                    <a href="?delete=<?php echo $ann['id']; ?>" class="btn btn-danger btn-small" style="text-decoration: none;" onclick="return confirm('Delete this announcement?')">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; padding: 40px; color: #666;">No announcements yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
