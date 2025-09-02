# PHPIndex v1.5

![Version](https://img.shields.io/badge/version-1.5-blue)
![License](https://img.shields.io/badge/license-MIT-green)

A modern, single-file PHP script for browsing a server's file directory with an intuitive web interface. It allows you to view file contents, generate download/execution commands for `wget`, `curl`, and `PowerShell`, and includes secure, password-protected file creation.

## Features

- **Single-File Deployment:** Drop `PHPIndex.php` into any directory to get started.
- **Subfolder Navigation:** Easily browse through nested directories.
- **Clickable Breadcrumbs:** Always know where you are and navigate back to parent folders with ease.
- **Live Filtering:** Instantly filter the file list using text and wildcards (`*.sh`).
- **Multi-Tool Command Generation:** Generate one-click copy commands for:
    - `wget`
    - `curl`
    - `PowerShell`
- **Download or Execute:** Choose between commands that simply download the file or download and pipe it directly to `bash` or `Invoke-Expression`.
- **Intelligent Defaults:** Automatically selects PowerShell for `.ps1` files.
- **Syntax Highlighting:** File previews for code are beautifully highlighted for improved readability.
- **Secure File Creation:** Log in to create new files directly from the UI.
- **Authentication:** Simple, secure password protection for all write actions.
- **Dark Mode:** A sleek dark mode that respects your system preference and can be toggled manually.
- **Responsive Design:** Looks great on both desktop and mobile devices.
- **Secure by Default:**
    - Directory traversal protection.
    - Cross-Site Scripting (XSS) protection via `htmlspecialchars`.
    - Command injection protection via `rawurlencode`.
    - CSRF token protection for all form submissions.

## Setup & Configuration

1.  **Download:** Place the `PHPIndex.php` file in the root of the directory you want to make accessible.
2.  **Set Password (Required for File Creation):**
    - Open `PHPIndex.php` in a text editor.
    - Locate the `--- CONFIGURATION ---` section at the top.
    - Change the value of the `$plainTextPassword` variable to your desired secure password:
      ```php
      $plainTextPassword = 'your_super_secret_password'; 
      ```
    - Upload the file. The first time the script is loaded, it will automatically generate a secure hash of your password.
    - **For enhanced security:** After the hash is generated (you'll see it in the `$passwordHash` variable if you re-download the file or view its source), you can delete the `$plainTextPassword` line entirely.

## Usage

- **Browsing:** Navigate to the script's URL in your browser. Click on folders to enter them and use the breadcrumbs to go back.
- **Filtering:** Type in the filter box to narrow down the file list.
- **Getting Commands:** Use the dropdowns next to each file to select your tool (`Wget`, `Curl`, `PowerShell`) and action (`Download`, `Execute`), then click "Copy".
- **Creating Files:**
    1. Click the "Login" button at the top right and enter the password you configured.
    2. Once logged in, a "Create New File" button will appear.
    3. Click it to open a form where you can provide a filename and content for your new file.

## License

This project is licensed under the MIT License.
