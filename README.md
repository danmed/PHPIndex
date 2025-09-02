# PHPIndex

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A powerful, single-file PHP script for browsing a server's file directory with a clean, modern interface. PHPIndex allows you to navigate subfolders, view file contents, and instantly generate download or execution commands for Wget, Curl, and PowerShell.

## Screenshots

<img width="1871" height="1177" alt="image" src="https://github.com/user-attachments/assets/e3ed8fb1-6492-4925-aa2a-b833af9a972a" />

## Features

* **Single-File Deployment:** Simply drop `PHPIndex.php` into any directory to get started.
* **Subfolder Navigation:** Easily browse through nested directories with a clickable breadcrumb trail.
* **Dynamic Command Generation:** Instantly create commands for:
    * **Tools:** `Wget`, `Curl`, and `PowerShell`.
    * **Actions:** Download to a file or pipe directly to `bash` / `Invoke-Expression` for execution.
* **Live Filtering:** Filter the file and folder list in real-time with wildcard support (e.g., `*.sh`, `config*`).
* **File Content Viewer:** Expand any file to view its contents directly in the browser.
* **Modern UI:**
    * Clean, responsive interface built with Tailwind CSS.
    * Includes a **Light/Dark Mode** toggle that respects system preferences.
* **Easy Configuration:** A simple PHP array lets you exclude specific files, extensions, or folders from the listing.
* **Secure:** The script is restricted to its own directory and subdirectories, preventing traversal to parent folders.

## How to Use

1.  **Download:** Get the latest version of `PHPIndex.php`.
2.  **Upload:** Place the `PHPIndex.php` file in the root of the directory you want to make available.
3.  **Navigate:** Open the file in your web browser (e.g., `http://your-server.com/your-folder/PHPIndex.php`).

That's it! The script will automatically list the contents of its directory.

## Configuration

To hide certain files or folders from the list, simply add their names to the `$excludeList` array at the top of the `PHPIndex.php` script. You can exclude by filename (e.g., `.git`) or by extension (e.g., `.css`).

```php
// --- CONFIGURATION ---
$excludeList = [
    'index.php',
    'PHPIndex.php',
    '.DS_Store',
    '.git',
    '.env', // Example: Exclude environment files
];
```

## License

This project is released under the MIT License. See the `LICENSE` file for details.
