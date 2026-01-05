# How to Run Your PHP App (VS Code vs XAMPP)

You asked about **"Live Server"** in VS Code.

**Important**: The standard "Live Server" extension (purple icon) **does NOT work with PHP**. It only works for HTML. If you use it, you will see raw code instead of your website.

Since `login.php` needs a "Brain" (PHP Engine) to run, you have two options:

---

### Option 1: The "Pro" Way (Recommended: XAMPP)
Since you are on Windows, this is the best way to get a full server + database.

1.  **Download XAMPP**: Search "Download XAMPP" and install it.
2.  **Start the Server**: Open "XAMPP Control Panel" and click **Start** next to **Apache** and **MySQL**.
3.  **Deploy your Code**:
    *   Find your XAMPP installation folder (usually `C:\xampp`).
    *   Open the `htdocs` folder inside it.
    *   **Copy** your entire `UMS` folder into `htdocs`.
4.  **Run it**:
    *   Open Chrome and type: `http://localhost/UMS/login.php`

### Option 2: The "VS Code" Way (Extension)
If you really want to run it directly from VS Code, you need a different extension.

1.  **Install PHP**: You must have PHP installed on your computer first. (Currently, your computer does *not* have it based on our checks).
    *   *If you install XAMPP, you get PHP automatically.*
2.  **Install Extension**: Search for **"PHP Server"** (by brapifra) in VS Code extensions.
3.  **Run**: Open `login.php`, right-click, and select **"PHP Server: Serve Project"**.

**My Advice**: Go with **Option 1 (XAMPP)**. It is reliable, creates the Database for you (which you need anyway), and simulates a real live environment perfectly.
