<?php
// Century Peak Cement - Ticketing System Setup

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'cpc_ticketing';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass);

if ($conn->connect_error) {
    die("<div style='color: red; font-family: Arial;'>Connection failed: " . $conn->connect_error . "</div>");
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Century Peak Cement - Ticketing System Setup</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 20px;
        }
        .setup-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #1a237e;
            margin-bottom: 10px;
            text-align: center;
        }
        .logo {
            text-align: center;
            font-size: 48px;
            margin-bottom: 10px;
        }
        .status-line {
            padding: 10px;
            margin: 5px 0;
            border-radius: 8px;
            font-size: 14px;
        }
        .success { background: #e8f5e8; color: #2e7d32; }
        .error { background: #ffebee; color: #c62828; }
        .info { background: #e3f2fd; color: #1565c0; }
        .btn {
            display: inline-block;
            padding: 14px 28px;
            background: linear-gradient(135deg, #1a237e, #3949ab);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            margin-top: 20px;
        }
        .credentials {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .credentials h3 {
            margin-top: 0;
            color: #1a237e;
        }
        .credentials strong {
            color: #3949ab;
        }
    </style>
</head>
<body>
    <div class='setup-container'>
        <div class='logo'>🏭</div>
        <h1>Century Peak Cement</h1>
        <h2 style='text-align: center; color: #666; margin-bottom: 30px;'>Ticketing System Setup</h2>";

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "<div class='status-line success'>✓ Database created successfully</div>";
} else {
    echo "<div class='status-line error'>✗ Error creating database: " . $conn->error . "</div>";
}

// Select database
$conn->select_db($db_name);

// Disable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Drop existing tables
$tables = ['announcements', 'ticket_activities', 'ticket_replies', 'tickets', 'departments', 'users'];
foreach ($tables as $table) {
    $conn->query("DROP TABLE IF EXISTS $table");
}

// Create users table
$sql = "CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    user_type ENUM('staff', 'employee') DEFAULT 'employee',
    department VARCHAR(50),
    employee_id VARCHAR(20),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
)";
if ($conn->query($sql) === TRUE) {
    echo "<div class='status-line success'>✓ Users table created</div>";
} else {
    echo "<div class='status-line error'>✗ Error: " . $conn->error . "</div>";
}

// Create departments table
$sql = "CREATE TABLE departments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    manager_id INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "<div class='status-line success'>✓ Departments table created</div>";
} else {
    echo "<div class='status-line error'>✗ Error: " . $conn->error . "</div>";
}

// Create tickets table
$sql = "CREATE TABLE tickets (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT(11) NOT NULL,
    assigned_to INT(11),
    department_id INT(11),
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(50),
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    closed_at TIMESTAMP NULL
)";
if ($conn->query($sql) === TRUE) {
    echo "<div class='status-line success'>✓ Tickets table created</div>";
} else {
    echo "<div class='status-line error'>✗ Error: " . $conn->error . "</div>";
}

// Create ticket_replies table
$sql = "CREATE TABLE ticket_replies (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    message TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "<div class='status-line success'>✓ Ticket replies table created</div>";
} else {
    echo "<div class='status-line error'>✗ Error: " . $conn->error . "</div>";
}

// Create announcements table
$sql = "CREATE TABLE announcements (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_by INT(11) NOT NULL,
    priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL
)";
if ($conn->query($sql) === TRUE) {
    echo "<div class='status-line success'>✓ Announcements table created</div>";
} else {
    echo "<div class='status-line error'>✗ Error: " . $conn->error . "</div>";
}

// Create ticket_activities table for tracking ticket events
$sql = "CREATE TABLE ticket_activities (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    old_value VARCHAR(100),
    new_value VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "<div class='status-line success'>✓ Ticket activities table created</div>";
} else {
    echo "<div class='status-line error'>✗ Error: " . $conn->error . "</div>";
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// Insert default departments
$departments = [
    ['IT', 'Information Technology and technical support'],
    ['Purchasing', 'Procurement and purchasing department'],
    ['Paragon', 'Paragon operations'],
    ['Accounting', 'Accounting and financial matters'],
    ['LAB CCR', 'Laboratory and quality control'],
    ['Truckscale', 'Truck weighing and scale operations'],
    ['New Warehouse', 'Warehouse and inventory management'],
    ['Motorpool', 'Vehicle and equipment fleet management'],
    ['Compliance', 'Safety and compliance issues'],
    ['Mining', 'Mining operations and extraction'],
    ['Geology', 'Geological survey and analysis'],
    ['Engineering', 'Engineering and technical services'],
    ['CPC Port', 'Port operations and shipping'],
    ['HR', 'Human resources and personnel matters'],
    ['Admin', 'General administrative support']
];

foreach ($departments as $dept) {
    $stmt = $conn->prepare("INSERT IGNORE INTO departments (name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $dept[0], $dept[1]);
    $stmt->execute();
}
echo "<div class='status-line success'>✓ Default departments added</div>";

// Create admin user
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, user_type, department, employee_id) VALUES (?, ?, ?, ?, 'staff', 'Engineering', 'ADMIN001')");
$admin_user = 'admin';
$admin_email = 'admin@centurypeak.ph';
$admin_name = 'System Administrator';
$stmt->bind_param("ssss", $admin_user, $admin_email, $admin_password, $admin_name);
if ($stmt->execute()) {
    echo "<div class='status-line success'>✓ Admin user created</div>";
} else {
    echo "<div class='status-line info'>ℹ Admin user may already exist</div>";
}

// Create sample employee user
$emp_password = password_hash('employee123', PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, user_type, department, employee_id) VALUES (?, ?, ?, ?, 'employee', 'Accounting', 'EMP001')");
$emp_user = 'employee';
$emp_email = 'employee@centurypeak.ph';
$emp_name = 'Juan Dela Cruz';
$stmt->bind_param("ssss", $emp_user, $emp_email, $emp_password, $emp_name);
$stmt->execute();

echo "
        <div class='credentials'>
            <h3>🔑 Login Credentials</h3>
            <p><strong>Admin Account:</strong></p>
            <p>Username: <strong>admin</strong><br>
            Password: <strong>admin123</strong><br>
            Type: Administrator</p>
            <hr style='margin: 15px 0; border: none; border-top: 1px solid #ddd;'>
            <p><strong>Sample Employee Account:</strong></p>
            <p>Username: <strong>employee</strong><br>
            Password: <strong>employee123</strong><br>
            Type: Employee</p>
        </div>
        
        <div style='text-align: center;'>
            <a href='index.php' class='btn'>Go to Login Page</a>
        </div>
    </div>
</body>
</html>";
    
$conn->close();
?>
