<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        redirect('../index.php?error=Please enter both username and password');
    }
    
    // Check user in database
    $sql = "SELECT id, username, email, password, full_name, user_type, department, employee_id 
            FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['department'] = $user['department'];
            $_SESSION['employee_id'] = $user['employee_id'];
            
            // Update last login
            $update_sql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            
            // Redirect based on user type
            if ($user['user_type'] === 'staff') {
                redirect('../admin/dashboard.php');
            } else {
                redirect('../employee/dashboard.php');
            }
        } else {
            redirect('../index.php?error=Invalid username or password');
        }
    } else {
        redirect('../index.php?error=Invalid username or password');
    }
} else {
    redirect('../index.php');
}
?>
