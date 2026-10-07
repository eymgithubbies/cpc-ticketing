<?php
require_once 'config.php';

$indexes = [
    "CREATE INDEX IF NOT EXISTS idx_tickets_user_id ON tickets(user_id)",
    "CREATE INDEX IF NOT EXISTS idx_tickets_assigned_to ON tickets(assigned_to)",
    "CREATE INDEX IF NOT EXISTS idx_tickets_department_id ON tickets(department_id)",
    "CREATE INDEX IF NOT EXISTS idx_tickets_status ON tickets(status)",
    "CREATE INDEX IF NOT EXISTS idx_tickets_priority ON tickets(priority)",
    "CREATE INDEX IF NOT EXISTS idx_tickets_created_at ON tickets(created_at)",
    "CREATE INDEX IF NOT EXISTS idx_tickets_updated_at ON tickets(updated_at)",
    "CREATE INDEX IF NOT EXISTS idx_ticket_replies_ticket_id ON ticket_replies(ticket_id)",
    "CREATE INDEX IF NOT EXISTS idx_ticket_replies_user_id ON ticket_replies(user_id)",
    "CREATE INDEX IF NOT EXISTS idx_ticket_activities_ticket_id ON ticket_activities(ticket_id)",
    "CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id)",
    "CREATE INDEX IF NOT EXISTS idx_notifications_is_read ON notifications(is_read)",
    "CREATE INDEX IF NOT EXISTS idx_notifications_created_at ON notifications(created_at)",
    "CREATE INDEX IF NOT EXISTS idx_announcements_is_active ON announcements(is_active)",
    "CREATE INDEX IF NOT EXISTS idx_announcements_expires_at ON announcements(expires_at)",
    "CREATE INDEX IF NOT EXISTS idx_it_status_admin_id ON it_status(admin_id)",
    "CREATE INDEX IF NOT EXISTS idx_it_status_updated_at ON it_status(updated_at)"
];

echo "Adding database indexes for performance optimization...\n\n";

foreach ($indexes as $index) {
    try {
        if ($conn->query($index)) {
            echo "✓ " . substr($index, 0, 50) . "...\n";
        } else {
            echo "✗ " . substr($index, 0, 50) . "... - " . $conn->error . "\n";
        }
    } catch (Exception $e) {
        echo "✗ " . substr($index, 0, 50) . "... - " . $e->getMessage() . "\n";
    }
}

echo "\nIndex creation complete!\n";
?>
