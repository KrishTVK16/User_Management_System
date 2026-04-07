-- Migration v4: Hierarchical Projects and Requirements
-- This script adds support for Master Projects and Sub-projects (Sites)

-- 1. Modify Projects Table
ALTER TABLE projects 
ADD COLUMN parent_id INT NULL AFTER id,
ADD COLUMN requirements TEXT AFTER description,
ADD COLUMN project_type VARCHAR(100) DEFAULT 'Static HTML' AFTER requirements,
ADD COLUMN estimated_days INT DEFAULT 2 AFTER project_type,
ADD CONSTRAINT fk_project_parent FOREIGN KEY (parent_id) REFERENCES projects(id) ON DELETE CASCADE;

-- 2. Index for parent_id performance
CREATE INDEX idx_project_parent ON projects(parent_id);

-- 3. Update Existing Projects (Set them as standalone/Master Projects)
-- (No action needed as parent_id is NULL by default)

-- 4. Add a test Master Project for March Slot
INSERT INTO projects (name, description, status) 
VALUES ('March 2026 Slot', 'Master project for March website rollout', 'Assigned');
