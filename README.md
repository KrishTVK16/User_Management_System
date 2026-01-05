# SmartFusion Team - Leave & Project Management System

A comprehensive Employee Management System built with PHP and MySQL. Features include project tracking, attendance logging, leave management, and an admin review system.

## Features

### Employee Portal
- **Dashboard**: View daily attendance status, active projects, and recent activity.
- **Attendance**: Clock In/Out and recording break times.
- **Daily Logs**: Submit work logs for assigned projects.
- **Projects**: View assigned projects and status.
- **Leaves**: Apply for leaves (Full Day, Half Day, Time Permission) and view history.
- **Profile**: Update password and view details.

### Admin Panel
- **Dashboard**: Overview of total employees, projects, and active attendance.
- **Manage Projects**: Create, edit, and assign projects to employees.
- **Employees**: Manage employee accounts.
- **Reports**: View work logs and attendance reports.
- **Evaluations**: Monthly attendance evaluation with automated status checks (Undertime, Absent, etc.).
- **Leave Management**: Approve/Reject leave requests.
- **Manual Review**: Granular control to excuse undertime or mark leaves for specific days with comments.

## Prerequisites

- **Web Server**: Apache (via XAMPP, WAMP, or similar)
- **PHP**: Version 7.4 or higher
- **Database**: MySQL / MariaDB

## Installation

1.  **Clone the Repository**
    Use the following command to download the project to your computer:
    ```bash
    git clone https://github.com/KrishTVK16/User_Management_System.git
    ```

2.  **Database Setup**
    - Open your database manager (e.g., phpMyAdmin at `http://localhost/phpmyadmin`).
    - Create a new database named `teampulse_db`.
    - Click **Import** and select the `database_setup.sql` file from the project folder.
    - Click **Go** to create the tables and default users.

3.  **Configuration**
    - Open the file `includes/db_connect.php` in a text editor.
    - Check the database settings. If you use XAMPP with default settings, you likely don't need to change anything:
      ```php
      $servername = "localhost";
      $username = "root";
      $password = ""; // Default is empty for XAMPP
      $dbname = "teampulse_db";
      ```

4.  **Run the Application**
    - Move the project folder (User_Management_System) to your server's public folder (e.g., `C:\xampp\htdocs\`).
    - Open your browser and visit: `http://localhost/User_Management_System`

## Default Credentials

### Admin
- **Username**: `admin@smartfusion.com`
- **Password**: `SFadmin@123`

### Employee (Starters)
- **Username**: `vamsi@smartfusion.com`
- **Password**: `SFvamsi@123`
