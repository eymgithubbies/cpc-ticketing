<?php
require_once '../config.php';

if (!is_logged_in() || !is_staff()) {
    redirect('../index.php');
}

// Filters
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$dept_filter = isset($_GET['dept']) ? (int)$_GET['dept'] : 0;
$priority_filter = isset($_GET['priority']) ? sanitize_input($_GET['priority']) : '';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$date_sort = isset($_GET['date_sort']) ? sanitize_input($_GET['date_sort']) : 'desc';

// Build query
$sql = "SELECT t.*, u.full_name as creator_name, d.name as department_name, assignee.full_name as assigned_name
        FROM tickets t 
        LEFT JOIN users u ON t.user_id = u.id 
        LEFT JOIN departments d ON t.department_id = d.id
        LEFT JOIN users assignee ON t.assigned_to = assignee.id
        WHERE 1=1";

$params = [];
$types = "";

if ($status_filter) {
    $sql .= " AND t.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}
if ($dept_filter) {
    $sql .= " AND t.department_id = ?";
    $params[] = $dept_filter;
    $types .= "i";
}
if ($priority_filter) {
    $sql .= " AND t.priority = ?";
    $params[] = $priority_filter;
    $types .= "s";
}
if ($search) {
    $sql .= " AND (t.ticket_number LIKE ? OR t.subject LIKE ? OR u.full_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$sql .= " ORDER BY t.created_at " . ($date_sort == 'asc' ? 'ASC' : 'DESC') . ",
    CASE t.priority 
        WHEN 'urgent' THEN 1 
        WHEN 'high' THEN 2 
        WHEN 'normal' THEN 3 
        WHEN 'low' THEN 4 
    END";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$tickets_result = $stmt->get_result();

// Get departments for filter (cached)
$departments = get_cached_departments($conn);

$success = isset($_GET['success']) ? $_GET['success'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Tickets - Century Peak Cement</title>
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
        .container { max-width: 1400px; margin: 0 auto; padding: 30px; }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
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
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            background: #e8f5e8;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        .filter-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .filter-select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        .search-input {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            width: 250px;
            outline: none;
            transition: all 0.3s;
        }
        .search-input:focus {
            border-color: #3949ab;
            box-shadow: 0 0 0 3px rgba(57, 73, 171, 0.1);
        }
        .search-form {
            display: flex;
            gap: 10px;
            align-items: center;
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
        .ticket-link {
            color: #3949ab;
            text-decoration: none;
            font-weight: 600;
        }
        .ticket-link:hover { text-decoration: underline; }
        .subject-cell {
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            text-decoration: none;
            font-weight: 600;
        }
        .action-view {
            background: #e3f2fd;
            color: #1976d2;
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
            <a href="all_tickets.php" class="active">🎫 All Tickets</a>
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
            <h1>📋 All Tickets</h1>
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
        
        <?php if ($success): ?>
            <div class="alert">
                <span>✓</span> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <div class="filter-bar">
            <span style="color: #666; font-weight: 600;">Filters:</span>
            <a href="all_tickets.php<?php echo $search ? '?search='.urlencode($search) : ''; ?>" class="filter-btn <?php echo !$status_filter && !$dept_filter && !$priority_filter ? 'active' : ''; ?>">All</a>
            <a href="all_tickets.php?status=open<?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="filter-btn <?php echo $status_filter == 'open' ? 'active' : ''; ?>">Open</a>
            <a href="all_tickets.php?status=in_progress<?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="filter-btn <?php echo $status_filter == 'in_progress' ? 'active' : ''; ?>">In Progress</a>
            <a href="all_tickets.php?priority=urgent<?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="filter-btn <?php echo $priority_filter == 'urgent' ? 'active' : ''; ?>">Urgent</a>
            
            <div class="filter-group" style="margin-left: auto;">
                <form method="GET" class="search-form">
                    <input type="text" name="search" class="search-input" placeholder="Search tickets..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary" style="padding: 8px 16px;">Search</button>
                    <?php if ($search): ?>
                        <a href="all_tickets.php<?php echo $status_filter ? '?status='.$status_filter : ($dept_filter ? '?dept='.$dept_filter : ($priority_filter ? '?priority='.$priority_filter : '')); ?>" class="btn btn-secondary" style="padding: 8px 16px;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="filter-group">
                <form method="GET" style="display: flex; gap: 10px;">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <select name="dept" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $id => $name): ?>
                            <option value="<?php echo $id; ?>" <?php echo $dept_filter == $id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            
            <div class="filter-group">
                <form method="GET" style="display: flex; gap: 10px;">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <?php if ($status_filter): ?><input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>"><?php endif; ?>
                    <?php if ($dept_filter): ?><input type="hidden" name="dept" value="<?php echo $dept_filter; ?>"><?php endif; ?>
                    <?php if ($priority_filter): ?><input type="hidden" name="priority" value="<?php echo htmlspecialchars($priority_filter); ?>"><?php endif; ?>
                    <select name="date_sort" class="filter-select" onchange="this.form.submit()">
                        <option value="desc" <?php echo $date_sort == 'desc' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="asc" <?php echo $date_sort == 'asc' ? 'selected' : ''; ?>>Oldest First</option>
                    </select>
                </form>
            </div>
        </div>
        
        <div class="card">
            <h2>Tickets (<?php echo $tickets_result->num_rows; ?>)</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Subject</th>
                        <th>Created By</th>
                        <th>Department</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Created</th>
                        <th>Action</th>
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
                                <td><?php echo htmlspecialchars($ticket['creator_name']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['department_name'] ?? 'General'); ?></td>
                                <td><?php echo htmlspecialchars($ticket['assigned_name'] ?? 'Not Assigned'); ?></td>
                                <td><span class="badge" style="background: <?php echo $status_badge['bg']; ?>; color: <?php echo $status_badge['color']; ?>"><?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?></span></td>
                                <td><span class="badge" style="background: <?php echo $priority_badge['bg']; ?>; color: <?php echo $priority_badge['color']; ?>"><?php echo ucfirst($ticket['priority']); ?></span></td>
                                <td><?php echo date('M j, Y', strtotime($ticket['created_at'])); ?></td>
                                <td><a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="action-btn action-view">View</a></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align: center; padding: 40px; color: #666;">No tickets found matching your filters</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
