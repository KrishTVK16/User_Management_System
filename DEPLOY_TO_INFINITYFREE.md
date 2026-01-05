# How to Deploy to InfinityFree (Free Hosting)

Since you selected **InfinityFree** for your long-term hosting, here is a step-by-step guide to putting your **User Management System** online properly.

## Prerequisite
Make sure you have downloaded or cloned your project files to your computer.

## Step 1: Sign Up & Create Account
1.  Go to [InfinityFree.com](https://www.infinityfree.com/) and Sign Up.
2.  After logging in, click **"Create Account"**.
3.  **Choose a Domain**:
    *   Select "Subdomain" (Free).
    *   Enter a name (e.g., `smartfusion-team`) and choose a domain extension (e.g., `.rf.gd`).
4.  **Account Password**: Generate a random password or set your own.
5.  Click **Create Account**.

## Step 2: Upload Your Files
1.  In the InfinityFree Dashboard (Client Area), click **"Manage"** next to your new account.
2.  Click **"File Manager"** (opens in a new tab).
3.  Open the **`htdocs`** folder.
    *   *Note: Delete the default `index2.html` or `default` file if you see one.*
4.  **Upload**:
    *   Click the "Upload" icon (or right-click > Upload).
    *   Select **File** for individual files or **Folder** (if your browser supports it).
    *   **Crucial**: Upload **ALL** files and folders from your project (`css`, `js`, `includes`, `actions`, `*.php`) **INSIDE** the `htdocs` folder.
    *   *Do NOT upload the `.git` folder or `DEMO_TO_INFINITYFREE.md`.*

## Step 3: Setup the Database
1.  Go back to the **Client Area / Control Panel** (VistaPanel).
2.  Look for the **"MySQL Databases"** icon.
3.  **Create New Database**:
    *   Enter a Database Name (e.g., `teampulse`).
    *   Click **Create Database**.
    *   **COPY** the details provided:
        *   **MySQL Host Name** (e.g., `sql300.infinityfree.com`)
        *   **MySQL User Name** (e.g., `if0_36220188`)
        *   **MySQL Password** (Your hosting account password, or listed there).
        *   **MySQL Database Name** (e.g., `if0_36220188_teampulse`).
4.  **Import Tables**:
    *   Click **"Admin"** button next to your database (opens phpMyAdmin).
    *   Click the **"Import"** tab at the top.
    *   Choose your `database_setup.sql` file.
    *   Click **Go**.

## Step 4: Connect PHP to Database
You need to tell your PHP code the *new* database credentials because "localhost" and "root" won't work there.

1.  In the **File Manager** (inside `htdocs/includes/`).
2.  Right-click `db_connect.php` and select **Edit**.
3.  Update the lines with the specific details from Step 3:
    ```php
    $servername = "sql300.infinityfree.com"; // Your MySQL Host Name
    $username = "if0_36220188"; // Your MySQL User Name
    $password = "YourVpanelPassword"; // Your Password
    $dbname = "if0_36220188_teampulse"; // Your Database Name
    ```
4.  **Save** the file.

## Step 5: Test It!
1.  Open your browser and type your domain name (e.g., `http://smartfusion-team.rf.gd`).
2.  You should see your Login Page!
3.  **Login** with the default credentials:
    *   Admin: `admin@smartfusion.com` / `SFadmin@123`

---
**Done!** Your site is now live on the internet permanently (as long as it gets some traffic).
