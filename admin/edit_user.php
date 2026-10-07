<?php
require_once '../config.php';

if (!is_logged_in() || !is_staff()) {
    redirect('../index.php');
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$user_id) {
    redirect('staff.php');
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows === 0) {
    redirect('staff.php');
}

$user = $user_result->fetch_assoc();

// Get departments for dropdown
$dept_result = $conn->query("SELECT name FROM departments ORDER BY name");
$departments = [];
while ($row = $dept_result->fetch_assoc()) {
    $departments[] = $row['name'];
}

$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Century Peak Cement</title>
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
        .container { max-width: 600px; margin: 0 auto; padding: 30px; }
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
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
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
        .card h2 { margin-bottom: 25px; color: #1a237e; }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        .form-group label .required {
            color: #c62828;
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
        select.form-control {
            cursor: pointer;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .info-box {
            background: #fff3e0;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #e65100;
            font-size: 13px;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }
        .button-group .btn {
            flex: 1;
        }
        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
            .button-group { flex-direction: column; }
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
            <a href="staff.php" class="active">� Users</a>
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
            <h1>✏️ Edit User</h1>
            <a href="staff.php" class="btn btn-secondary">← Back to Users</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <span>⚠️</span> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>User Information: <?php echo htmlspecialchars($user['full_name']); ?></h2>
            
            <div class="info-box">
                <span>ℹ️</span> Leave password fields blank to keep the current password.
            </div>
            
            <form action="update_user.php" method="POST">
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Username <span class="required">*</span></label>
                        <input type="text" name="username" class="form-control" 
                               value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Employee ID <span class="required">*</span></label>
                        <input type="text" name="employee_id" class="form-control" 
                               value="<?php echo htmlspecialchars($user['employee_id']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Full Name <span class="required">*</span></label>
                    <input type="text" name="full_name" class="form-control" 
                           value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Department <span class="required">*</span></label>
                        <select name="department" class="form-control" required>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" 
                                    <?php echo ($user['department'] === $dept) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>User Type <span class="required">*</span></label>
                        <select name="user_type" class="form-control" required>
                            <option value="employee" <?php echo ($user['user_type'] === 'employee') ? 'selected' : ''; ?>>Employee</option>
                            <option value="staff" <?php echo ($user['user_type'] === 'staff') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" class="form-control" 
                               placeholder="Leave blank to keep current">
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                    <a href="staff.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
