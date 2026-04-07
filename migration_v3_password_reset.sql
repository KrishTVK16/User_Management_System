-- migration_v3_password_reset.sql
-- Add columns for password reset functionality

ALTER TABLE users 
ADD COLUMN reset_token_hash VARCHAR(64) NULL,
ADD COLUMN reset_token_expiry DATETIME NULL;
