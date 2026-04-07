-- Migration Script for Project Lifecycle Management System

-- 1. Update Users Table
ALTER TABLE users 
MODIFY COLUMN role ENUM('admin', 'employee', 'super_admin') NOT NULL DEFAULT 'employee',
ADD COLUMN sub_role ENUM('Developer', 'Tester', 'Full Stack', 'None') DEFAULT 'None' AFTER role;

-- 2. Update Projects Table
ALTER TABLE projects 
ADD COLUMN client_name VARCHAR(100) AFTER name,
ADD COLUMN project_link VARCHAR(255) AFTER client_name,
ADD COLUMN developer_id INT AFTER description,
ADD COLUMN tester_id INT AFTER developer_id,
ADD COLUMN assigned_at TIMESTAMP NULL AFTER tester_id,
ADD COLUMN started_at TIMESTAMP NULL AFTER assigned_at,
ADD COLUMN completed_at TIMESTAMP NULL AFTER started_at,
ADD COLUMN finalized_at TIMESTAMP NULL AFTER completed_at,
ADD COLUMN submitted_at TIMESTAMP NULL AFTER finalized_at,
ADD COLUMN initial_notes TEXT AFTER submitted_at,
ADD COLUMN completion_link VARCHAR(255) AFTER initial_notes,
ADD COLUMN completion_notes TEXT AFTER completion_link,
ADD COLUMN fix_notes TEXT AFTER completion_notes,
ADD COLUMN is_delayed TINYINT(1) DEFAULT 0 AFTER fix_notes,
MODIFY COLUMN status ENUM(
    'Assigned', 
    'Development Initialized', 
    'Development Completed', 
    'Testing', 
    'Correction Required', 
    'Corrected', 
    'Finalized', 
    'Client Submitted'
) DEFAULT 'Assigned',
ADD FOREIGN KEY (developer_id) REFERENCES users(id) ON DELETE SET NULL,
ADD FOREIGN KEY (tester_id) REFERENCES users(id) ON DELETE SET NULL;

-- 3. Project History Table (Audit Log)
CREATE TABLE IF NOT EXISTS project_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    from_status VARCHAR(50),
    to_status VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. Project Corrections Table
CREATE TABLE IF NOT EXISTS project_corrections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    tester_id INT NOT NULL,
    developer_id INT NOT NULL,
    correction_notes TEXT NOT NULL,
    attachment_path VARCHAR(255),
    fix_notes TEXT,
    is_fixed TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fixed_at TIMESTAMP NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (tester_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (developer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 5. Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'alert') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- SEED: Update existing projects to 'Assigned' status if any exist
UPDATE projects SET status = 'Assigned' WHERE status NOT IN ('Assigned', 'Development Initialized', 'Development Completed', 'Testing', 'Correction Required', 'Corrected', 'Finalized', 'Client Submitted');
