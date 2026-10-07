<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize_input($_POST['full_name']);
    $department = sanitize_input($_POST['department']);
    // Generate default values for removed fields
    $email = $username . '@cpcmc.local';
    $employee_id = 'EMP' . strtoupper(substr($username, 0, 3)) . rand(100, 999);
    $phone = '';

    // Validation
    $errors = [];

    if (empty($username) || strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    }

    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }

    if (empty($department)) {
        $errors[] = "Department is required";
    }

    // Check if username already exists
    $check_sql = "SELECT id FROM users WHERE username = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $errors[] = "Username already exists";
    }

    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $insert_sql = "INSERT INTO users (username, email, password, full_name, user_type, department, employee_id, phone) VALUES (?, ?, ?, ?, 'employee', ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("sssssss", $username, $email, $hashed_password, $full_name, $department, $employee_id, $phone);

        if ($stmt->execute()) {
            // Log successful registration
            $new_user_id = $stmt->insert_id;
            $log_message = sprintf(
                "[%s] REGISTRATION SUCCESS: ID=%d, Username=%s, Email=%s, Name=%s, EmployeeID=%s, Department=%s, IP=%s\n",
                date('Y-m-d H:i:s'),
                $new_user_id,
                $username,
                $email,
                $full_name,
                $employee_id,
                $department,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            );
            
            // Write to log file
            $log_file = __DIR__ . '/../logs/registration.log';
            if (!file_exists(dirname($log_file))) {
                mkdir(dirname($log_file), 0755, true);
            }
            file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
            
            redirect('../index.php?success=Registration successful! Please login.');
        } else {
            redirect('../register.php?error=Registration failed. Please try again.');
        }
    } else {
        $error_string = urlencode(implode(', ', $errors));
        redirect('../register.php?error=' . $error_string);
    }
} else {
    redirect('../register.php');
}
?>
