<?php
require_once '../config.php';

if (!is_logged_in() || !is_staff()) {
    redirect('../index.php');
}

$dept_result = $conn->query("SELECT d.*, u.full_name as manager_name,
    (SELECT COUNT(*) FROM users WHERE department = d.name) as user_count
    FROM departments d 
    LEFT JOIN users u ON d.manager_id = u.id 
    ORDER BY d.name");

// Get all users grouped by department for display
$users_by_dept = [];
$all_users = $conn->query("SELECT id, full_name, username, email, user_type, department, employee_id FROM users ORDER BY full_name");
while ($user = $all_users->fetch_assoc()) {
    $dept_name = $user['department'] ?? 'Unassigned';
    if (!isset($users_by_dept[$dept_name])) {
        $users_by_dept[$dept_name] = [];
    }
    $users_by_dept[$dept_name][] = $user;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments - Century Peak Cement</title>
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
        .btn-secondary {
            background: white;
            color: #1a237e;
            border: 2px solid #1a237e;
        }
        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .card h2 { margin-bottom: 20px; color: #1a237e; }
        .dept-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .dept-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #3949ab;
        }
        .dept-card h3 {
            color: #1a237e;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .dept-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .dept-manager {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #555;
        }
        .dept-card {
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .dept-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .user-count {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #3949ab;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 700px;
            max-height: 80vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .modal-header {
            background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%);
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h2 {
            margin: 0;
            font-size: 20px;
        }
        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }
        .modal-body {
            padding: 25px;
            max-height: 60vh;
            overflow-y: auto;
        }
        .user-list {
            display: grid;
            gap: 10px;
        }
        .user-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .user-avatar-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }
        .user-details h4 {
            color: #333;
            margin-bottom: 3px;
        }
        .user-details p {
            color: #666;
            font-size: 13px;
        }
        .badge-type {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-staff {
            background: #ff9800;
            color: white;
        }
        .badge-employee {
            background: #2196f3;
            color: white;
        }
        .empty-users {
            text-align: center;
            padding: 40px;
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
            <a href="departments.php" class="active">🏢 Departments</a>
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
            <h1>🏢 Departments</h1>
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
        
        <div class="card">
            <h2>Company Departments</h2>
            <div class="dept-grid">
                <?php while ($dept = $dept_result->fetch_assoc()): 
                    $dept_name = $dept['name'];
                    $user_count = $dept['user_count'];
                    $users_in_dept = $users_by_dept[$dept_name] ?? [];
                    $users_json = htmlspecialchars(json_encode($users_in_dept), ENT_QUOTES, 'UTF-8');
                ?>
                    <div class="dept-card" onclick="showDepartmentUsers('<?php echo htmlspecialchars($dept_name); ?>', '<?php echo htmlspecialchars($dept['description'] ?? 'No description'); ?>', <?php echo $user_count; ?>, '<?php echo $users_json; ?>')">
                        <h3><?php echo htmlspecialchars($dept_name); ?></h3>
                        <p><?php echo htmlspecialchars($dept['description'] ?? 'No description'); ?></p>
                        <div class="dept-manager">
                            <span>👤</span>
                            <span>Manager: <?php echo htmlspecialchars($dept['manager_name'] ?? 'Not assigned'); ?></span>
                        </div>
                        <div class="user-count">
                            <span>👥</span>
                            <span><?php echo $user_count; ?> User<?php echo $user_count !== 1 ? 's' : ''; ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Modal for showing department users -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Department Users</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p id="modalDescription" style="color: #666; margin-bottom: 20px;"></p>
                <div id="userList" class="user-list"></div>
            </div>
        </div>
    </div>

    <script>
        function showDepartmentUsers(deptName, description, userCount, usersJson) {
            const modal = document.getElementById('userModal');
            const title = document.getElementById('modalTitle');
            const desc = document.getElementById('modalDescription');
            const list = document.getElementById('userList');
            
            title.textContent = deptName + ' (' + userCount + ' Users)';
            desc.textContent = description;
            
            let users = [];
            try {
                users = JSON.parse(usersJson);
            } catch(e) {
                users = [];
            }
            
            if (users.length === 0) {
                list.innerHTML = '<div class="empty-users">👤 No users in this department</div>';
            } else {
                list.innerHTML = users.map(user => {
                    const badgeClass = user.user_type === 'staff' ? 'badge-staff' : 'badge-employee';
                    const avatar = user.full_name.charAt(0).toUpperCase();
                    return `
                        <div class="user-item">
                            <div class="user-info">
                                <div class="user-avatar-small">${avatar}</div>
                                <div class="user-details">
                                    <h4>${escapeHtml(user.full_name)}</h4>
                                    <p>${escapeHtml(user.email)} | ID: ${escapeHtml(user.employee_id || 'N/A')}</p>
                                </div>
                            </div>
                            <span class="badge-type ${badgeClass}">${user.user_type.toUpperCase()}</span>
                        </div>
                    `;
                }).join('');
            }
            
            modal.classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('userModal').classList.remove('active');
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Close modal when clicking outside
        document.getElementById('userModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
