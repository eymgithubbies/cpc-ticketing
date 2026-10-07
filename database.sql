-- =====================================================
-- Century Peak Cement Ticketing System Database
-- Timezone: Asia/Manila (UTC+8)
-- =====================================================

SET time_zone = '+08:00';

CREATE DATABASE IF NOT EXISTS cpc_ticketing
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE cpc_ticketing;

SET time_zone = '+08:00';

-- ===========================
-- Users Table
-- ===========================
CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    user_type ENUM('staff','employee') DEFAULT 'employee',
    department VARCHAR(50),
    employee_id VARCHAR(20),
    phone VARCHAR(20),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT NULL
);

-- ===========================
-- Departments Table
-- ===========================
CREATE TABLE departments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    manager_id INT(11),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ===========================
-- Tickets Table
-- ===========================
CREATE TABLE tickets (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT(11) NOT NULL,
    assigned_to INT(11),
    department_id INT(11),
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(50),
    priority ENUM('low','normal','high','urgent') DEFAULT 'normal',
    status ENUM('open','in_progress','resolved','closed') DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL DEFAULT NULL,
    closed_at TIMESTAMP NULL DEFAULT NULL
);

-- ===========================
-- Ticket Replies
-- ===========================
CREATE TABLE ticket_replies (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    message TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ===========================
-- Announcements
-- ===========================
CREATE TABLE announcements (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_by INT(11) NOT NULL,
    priority ENUM('low','normal','high') DEFAULT 'normal',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL DEFAULT NULL
);

-- ===========================
-- Ticket Activities
-- ===========================
CREATE TABLE ticket_activities (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    old_value VARCHAR(100),
    new_value VARCHAR(100),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ===========================
-- Notifications
-- ===========================
CREATE TABLE notifications (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ===========================
-- IT Live Status
-- ===========================
CREATE TABLE it_status (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    admin_id INT(11) NOT NULL,
    current_activity VARCHAR(255),
    status ENUM('Available','Busy','On-site','In Progress','Break') DEFAULT 'Available',
    location VARCHAR(100),
    related_ticket_id INT(11) NULL,
    estimated_finish TIME NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (related_ticket_id) REFERENCES tickets(id) ON DELETE SET NULL,
    INDEX idx_admin_id (admin_id),
    INDEX idx_updated_at (updated_at)
);

-- ===========================
-- Performance Indexes
-- ===========================
CREATE INDEX idx_tickets_user_id ON tickets(user_id);
CREATE INDEX idx_tickets_assigned_to ON tickets(assigned_to);
CREATE INDEX idx_tickets_department_id ON tickets(department_id);
CREATE INDEX idx_tickets_status ON tickets(status);
CREATE INDEX idx_tickets_priority ON tickets(priority);
CREATE INDEX idx_tickets_created_at ON tickets(created_at);
CREATE INDEX idx_tickets_updated_at ON tickets(updated_at);
CREATE INDEX idx_ticket_replies_ticket_id ON ticket_replies(ticket_id);
CREATE INDEX idx_ticket_replies_user_id ON ticket_replies(user_id);
CREATE INDEX idx_ticket_activities_ticket_id ON ticket_activities(ticket_id);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);
CREATE INDEX idx_notifications_created_at ON notifications(created_at);
CREATE INDEX idx_announcements_is_active ON announcements(is_active);
CREATE INDEX idx_announcements_expires_at ON announcements(expires_at);

-- ===========================
-- Default Departments
-- ===========================
INSERT INTO departments (name, description) VALUES
('IT','Information Technology and technical support'),
('Purchasing','Procurement and purchasing department'),
('Paragon','Paragon operations'),
('Accounting','Accounting and financial matters'),
('LAB CCR','Laboratory and quality control'),
('Truckscale','Truck weighing and scale operations'),
('New Warehouse','Warehouse and inventory management'),
('Motorpool','Vehicle and equipment fleet management'),
('Compliance','Safety and compliance issues'),
('Geology','Geological survey and analysis'),
('Engineering','Engineering and technical services'),
('CPC Port','Port operations and shipping'),
('HR','Human resources and personnel matters'),
('Admin','General administrative support'),
('Logistics','Logistics and supply chain management'),
('Minerals','Minerals processing and management');

-- ===========================
-- Default Admin
-- Username: admin
-- Password: admin123
-- ===========================
INSERT INTO users
(username,email,password,full_name,user_type,department,employee_id)
VALUES
(
'admin',
'admin@centurypeak.ph',
'$2y$10$pqTQXWtpj6ETI1qsWesxceyMpH/WNzXcHgBRnt0g5skx7l5Bh63pa',
'System Administrator',
'staff',
'Engineering',
'ADMIN001'
);

-- ===========================
-- Default Employee
-- Username: employee
-- Password: employee123
-- ===========================
INSERT INTO users
(username,email,password,full_name,user_type,department,employee_id)
VALUES
(
'employee',
'employee@centurypeak.ph',
'$2y$10$aK6z2qStGvZMCLuWNWSPXeWYu41WcYX4KwxsbStmjZED6BYU6m30.',
'Juan Dela Cruz',
'employee',
'Accounting',
'EMP001'
);