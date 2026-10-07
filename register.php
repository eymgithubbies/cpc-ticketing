<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'staff') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: employee/dashboard.php');
    }
    exit();
}

require_once 'config.php';

$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';

// Get departments for dropdown
$dept_sql = "SELECT name FROM departments ORDER BY name";
$dept_result = $conn->query($dept_sql);
$departments = [];
while ($row = $dept_result->fetch_assoc()) {
    $departments[] = $row['name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT HELPDESK - Register</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            height: 100%;
        }

        body {
            background: #f5f7fa;
            min-height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            padding: 20px;
        }

        #accesspanel {
            max-width: 500px;
            margin: 40px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 0;
            overflow: hidden;
        }

        #accesspanel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%);
        }

        #litheader {
            text-align: center;
            padding: 30px 0 20px;
            font-size: 24px;
            color: #1a237e;
            font-weight: 700;
            margin: 0;
        }

        .subtitle {
            text-align: center;
            color: #666;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .inset {
            padding: 0 30px 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-size: 13px;
            font-weight: 500;
        }

        .form-group label .required {
            color: #ff0040;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 12px 15px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        select:focus {
            border-color: #3949ab;
            box-shadow: 0 0 0 3px rgba(57, 73, 171, 0.1);
        }

        input::placeholder,
        select option:first-child {
            color: #999;
        }

        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
        }

        select option {
            background: white;
            color: #333;
        }

        .p-container {
            padding: 0 30px 30px;
            margin: 0;
        }

        input[type="submit"] {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        input[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 35, 126, 0.3);
        }

        .alert-error {
            background: #ffebee;
            border: 1px solid #ffcdd2;
            color: #c62828;
            padding: 12px 15px;
            margin: 0 30px 20px;
            border-radius: 10px;
            font-size: 14px;
            text-align: center;
        }

        .alert-success {
            background: #e8f5e9;
            border: 1px solid #c8e6c9;
            color: #2e7d32;
            padding: 12px 15px;
            margin: 0 30px 20px;
            border-radius: 10px;
            font-size: 14px;
            text-align: center;
        }

        .links-section {
            display: flex;
            justify-content: center;
            padding: 0 30px 20px;
            font-size: 11px;
        }

        .links-section a {
            color: #444;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .links-section a:hover {
            color: #1a237e;
        }

        .system-info {
            position: fixed;
            bottom: 20px;
            left: 20px;
            color: #666;
            font-size: 11px;
        }

        @media (max-width: 480px) {
            #accesspanel {
                margin: 20px auto;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .inset, .p-container {
                padding-left: 20px;
                padding-right: 20px;
            }
        }
    </style>
</head>
<body>
    <form id="accesspanel" action="auth/register.php" method="POST">
        <h1 id="litheader">Create Account</h1>
        <div class="subtitle">Fill in your details to register</div>
        
        <?php if ($error): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="inset">
            <div class="form-group">
                <label for="username">Username <span class="required">*</span></label>
                <input type="text" id="username" name="username" placeholder="min 3 chars" required minlength="3" autocomplete="off">
            </div>

            <div class="form-group">
                <label for="full_name">Full Name <span class="required">*</span></label>
                <input type="text" id="full_name" name="full_name" placeholder="Enter full name" required autocomplete="off">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" placeholder="min 6 chars" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="re-enter" required>
                </div>
            </div>

            <div class="form-group">
                <label for="department">Department <span class="required">*</span></label>
                <select id="department" name="department" required>
                    <option value="">-- SELECT --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>">
                            <?php echo htmlspecialchars($dept); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <p class="p-container">
            <input type="submit" value="Register">
        </p>
        
        <div class="links-section">
            <a href="index.php">← Back to Login</a>
        </div>
    </form>

    <div class="system-info">
        IT HELPDESK v2.4
    </div>
</body>
</html>
