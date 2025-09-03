# PHPIndex

**Version:** 1.6  
**License:** MIT

PHPIndex is a single-file, zero-dependency script that provides a clean and modern interface for browsing a directory on your web server. It's designed for developers who need a quick and easy way to access files, generate download/execution commands, and perform basic file management.



## Features

- **Single-File Deployment:** Just drop `PHPIndex.php` into any directory.
- **Sub-folder Navigation:** Browse through subdirectories with clickable breadcrumb navigation.
- **Command Generation:** Instantly generate download or execution commands for `Wget`, `Curl`, and `PowerShell`.
- **Live File Filtering:** Filter the file list in real-time with wildcard support (`*.sh`, `script*`, etc.).
- **File Content Preview:** View the contents of any file with syntax highlighting.
- **Dark Mode:** Includes a sleek dark mode with automatic theme detection and a manual toggle.
- **Secure File Management (Login Protected):**
    - Create new files.
    - Edit existing files.
    - Delete files.
- **Secure by Default:** Features built-in protection against directory traversal, XSS, and CSRF attacks.

---

## Setup

1.  **Download:** Get the `PHPIndex.php` file.
2.  **Upload:** Place the file in the directory you want to browse on your web server.
3.  **(Recommended) Set Your Password:**
    - Open `PHPIndex.php` in a text editor.
    - **To set/reset the password:** Find the line that starts with `$passwordHash = ` and change it to look exactly like this: `$passwordHash = '';` (the hash must be empty).
    - Next, find the line `$plainTextPassword = 'your_password_here';` and change `'your_password_here'` to your new secure password.
    - Save and upload the file. The script will automatically generate a new secure hash from your plain text password the first time you log in.

That's it! You can now access the directory through your browser.

---

## Troubleshooting & Usage

### How do I edit or delete existing files?

The edit and delete functions require you to be logged in. If you have not set a password, these features will not be available.

If you are logged in but still can't edit or delete a file that you uploaded via FTP/SSH, it is almost certainly a **file permissions** issue.

The web server runs as a specific user (e.g., `www-data` or `apache`), and that user needs permission to modify files owned by your FTP/SSH user.

#### Quick Fix (Recommended)

The easiest solution is to change the "owner" of the project files to the web server user.

1.  **Find your web server's user:** This is commonly `www-data` on Debian/Ubuntu systems or `apache` on CentOS/RHEL systems.
2.  **Connect to your server via SSH.**
3.  **Run the `chown` (change owner) command:**
    - Navigate to the parent directory of where you placed `PHPIndex.php`.
    - Run the following command, replacing `www-data:www-data` if your server user is different and `/path/to/your/project` with the correct directory path.

    ```bash
    sudo chown -R www-data:www-data /path/to/your/project
    ```
    The `-R` flag makes the change recursive, applying it to all files and folders inside.

#### Alternative Fix (If you can't change ownership)

If you must keep the files owned by your personal user, you can grant "group" write permissions instead.

1.  **Find the web server's group name** (usually the same as the user, e.g., `www-data`).
2.  **Add your user to the web server's group:**

    ```bash
    sudo usermod -a -G www-data your_username
    ```
    You will need to log out and log back in for this change to take effect.
3.  **Grant write permissions to the group** for your project directory:
    ```bash
    sudo chmod -R g+w /path/to/your/project
    ```

After applying either of these fixes, the edit and delete functions in PHPIndex should work as expected.
