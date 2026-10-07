<?php
require_once 'config.php';

$sql = "CREATE TABLE it_status (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    admin_id INT(11) NOT NULL,
    current_activity VARCHAR(255),
    status ENUM('Available','Busy','On-site','In Progress','Break') DEFAULT 'Available',
    location VARCHAR(100),
    related_ticket_id INT(11) NULL,
    estimated_finish TIME NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (related_ticket_id) REFERENCES tickets(id) ON DELETE SET NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Table it_status created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}
?>
