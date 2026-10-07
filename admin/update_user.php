<?php
require_once '../config.php';

if (!is_logged_in() || !is_staff()) {
    redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('staff.php');
}

$user_id = intval($_POST['user_id']);
$username = sanitize_input($_POST['username']);
$employee_id = sanitize_input($_POST['employee_id']);
$full_name = sanitize_input($_POST['full_name']);
$email = sanitize_input($_POST['email']);
$department = sanitize_input($_POST['department']);
$user_type = sanitize_input($_POST['user_type']);
$phone = sanitize_input($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';

// Validation
$errors = [];

if (empty($username) || strlen($username) < 3) {
    $errors[] = "Username must be at least 3 characters";
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email is required";
}

if (empty($full_name)) {
    $errors[] = "Full name is required";
}

if (empty($employee_id)) {
    $errors[] = "Employee ID is required";
}

if (empty($department)) {
    $errors[] = "Department is required";
}

// Check if username or email already exists for OTHER users
$check_sql = "SELECT id FROM users WHERE (username = ? OR email = ? OR employee_id = ?) AND id != ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("sssi", $username, $email, $employee_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $errors[] = "Username, email, or employee ID already exists for another user";
}

if (!empty($errors)) {
    $error_string = urlencode(implode(', ', $errors));
    redirect("edit_user.php?id=$user_id&error=$error_string");
}

// Build update query
if (!empty($password)) {
    // Update with new password
    if (strlen($password) < 6) {
        redirect("edit_user.php?id=$user_id&error=Password must be at least 6 characters");
    }
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $update_sql = "UPDATE users SET username = ?, employee_id = ?, full_name = ?, email = ?, 
                   department = ?, user_type = ?, phone = ?, password = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssssssssi", $username, $employee_id, $full_name, $email, 
                     $department, $user_type, $phone, $hashed_password, $user_id);
} else {
    // Update without changing password
    $update_sql = "UPDATE users SET username = ?, employee_id = ?, full_name = ?, email = ?, 
                   department = ?, user_type = ?, phone = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("sssssssi", $username, $employee_id, $full_name, $email, 
                     $department, $user_type, $phone, $user_id);
}

if ($stmt->execute()) {
    redirect("staff.php?success=User updated successfully");
} else {
    redirect("edit_user.php?id=$user_id&error=Failed to update user");
}
?>
