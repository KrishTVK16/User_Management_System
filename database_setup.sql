-- Database Creation
CREATE DATABASE IF NOT EXISTS teampulse_db;
USE teampulse_db;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'employee') NOT NULL DEFAULT 'employee',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Projects Table
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('Active', 'On Hold', 'Completed') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Project Assignments (Linking Users to Projects)
CREATE TABLE IF NOT EXISTS project_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- 4. Attendance Table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    login_time DATETIME NOT NULL,
    logout_time DATETIME,
    total_work_hours DECIMAL(5, 2) DEFAULT 0.00,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 5. Breaks Table
CREATE TABLE IF NOT EXISTS breaks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attendance_id INT NOT NULL,
    type ENUM('Lunch', 'Tea', 'Personal') DEFAULT 'Personal',
    start_time DATETIME NOT NULL,
    end_time DATETIME,
    duration_minutes INT DEFAULT 0,
    FOREIGN KEY (attendance_id) REFERENCES attendance(id) ON DELETE CASCADE
);

-- 6. Daily Work Logs
CREATE TABLE IF NOT EXISTS daily_work_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT,
    date DATE NOT NULL,
    description TEXT NOT NULL,
    status ENUM('In Progress', 'Completed', 'Blocked') DEFAULT 'In Progress',
    time_spent_minutes INT DEFAULT 0,
    admin_comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
);

-- SEED DATA (Requested Logistics)
-- Admin: admin@smartfusion.com / SFadmin@123
INSERT INTO users (username, password_hash, full_name, role, email) VALUES 
('admin@smartfusion.com', '$2y$10$mKw8YDIROYBE9pimVRMwFOo0jY3iaNqYamIX0vopPuFEsIOVUezti', 'SmartFusion Admin', 'admin', 'admin@smartfusion.com');

-- Employee: vamsi@smartfusion.com / SFvamsi@123
INSERT INTO users (username, password_hash, full_name, role, email) VALUES 
('vamsi@smartfusion.com', '$2y$10$r4uBEZG4KWCjworMQDjQcOchQvNR7hbsSx4OFhMvdIF.GnvjZP1be', 'Vamsi Krishna', 'employee', 'vamsi@smartfusion.com');

-- TEST PROJECTS
INSERT INTO projects (name, description) VALUES 
('Website Redesign', 'Revamping the corporate website with new branding'),
('Mobile App API', 'Developing the backend API for the mobile application');

-- 7. Leave Requests Table
CREATE TABLE IF NOT EXISTS leave_requests (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    type ENUM('Full Day Leave', 'Half Day', 'Time Permission') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    admin_comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
