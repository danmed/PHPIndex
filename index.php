<?php
// --- PHP SCRIPT START ---
session_start();

// Version 1.6

// --- CONFIGURATION ---
// To disable write protection, set this to false.
define('WRITE_PROTECTION_ENABLED', true);
// IMPORTANT: Change this password. The script will automatically hash it.
// To generate a new hash, delete the $passwordHash line and set a new plain text password here.
$plainTextPassword = 'your_password_here'; 
$passwordHash = '$2y$10$If..He1.Q2ustcR0A3EgDuYVL5k/s3o5Jd293.KM8kaYCSa3yv65m'; // Default: 'your_password_here'

// --- Automatically create hash if not present ---
if (isset($plainTextPassword) && $passwordHash === '') {
    $passwordHash = password_hash($plainTextPassword, PASSWORD_DEFAULT);
}

$excludeList = [
    'index.php',
    'PHPIndex.php',
    '.DS_Store',
    '.git',
];
$powershellExtensions = ['ps1', 'psm1', 'psd1'];

// --- AUTHENTICATION & ACTION HANDLING ---
$isLoggedIn = (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true);
$feedbackMessage = '';
$feedbackType = ''; // 'success' or 'error'

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (isset($_POST['password']) && password_verify($_POST['password'], $passwordHash)) {
        $_SESSION['loggedin'] = true;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate CSRF token on login
        $isLoggedIn = true;
    } else {
        $feedbackMessage = 'Invalid password.';
        $feedbackType = 'error';
    }
}

// --- PATH HANDLING & SECURITY ---
$baseDir = __DIR__;
$relativePath = isset($_GET['path']) ? trim($_GET['path'], '/') : '.';
$requestedPath = $baseDir . DIRECTORY_SEPARATOR . $relativePath;
$realPath = realpath($requestedPath);

if ($realPath === false || strpos($realPath, $baseDir) !== 0 || !is_dir($realPath)) {
    $realPath = $baseDir;
    $relativePath = '.';
} else {
    $relativePath = trim(substr($realPath, strlen($baseDir)), DIRECTORY_SEPARATOR);
    if ($relativePath === '') $relativePath = '.';
}

// --- FILE ACTION HANDLING (with CSRF protection) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['login'])) {
    if (!$isLoggedIn || !WRITE_PROTECTION_ENABLED) {
        $feedbackMessage = 'Error: You must be logged in to perform actions.';
        $feedbackType = 'error';
    } elseif (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $feedbackMessage = 'CSRF token validation failed. Please try again.';
        $feedbackType = 'error';
    } else {
        // CSRF is valid, now process the specific action
        
        // --- FILE CREATION HANDLING ---
        if (isset($_POST['create_file'])) {
            $filename = $_POST['filename'] ?? '';
            $content = $_POST['file_content'] ?? '';
            $safeFilename = basename($filename);

            if (!empty($safeFilename)) {
                $newFilePath = $realPath . DIRECTORY_SEPARATOR . $safeFilename;
                if (file_exists($newFilePath)) {
                    $feedbackMessage = 'Error: A file with that name already exists.';
                    $feedbackType = 'error';
                } elseif (!is_writable($realPath)) {
                     $feedbackMessage = 'Error: The directory is not writable. Check permissions.';
                     $feedbackType = 'error';
                } else {
                    if (file_put_contents($newFilePath, $content) !== false) {
                        $feedbackMessage = 'File "' . htmlspecialchars($safeFilename) . '" created successfully.';
                        $feedbackType = 'success';
                    } else {
                        $feedbackMessage = 'Error: Could not write to file. Check permissions.';
                        $feedbackType = 'error';
                    }
                }
            } else {
                $feedbackMessage = 'Error: Filename cannot be empty.';
                $feedbackType = 'error';
            }
        }
        
        // --- FILE EDITING HANDLING ---
        if (isset($_POST['edit_file'])) {
            $filename = $_POST['filename'] ?? '';
            $content = $_POST['file_content'] ?? '';
            $safeFilename = basename($filename);
            $filePath = $realPath . DIRECTORY_SEPARATOR . $safeFilename;

            if (!file_exists($filePath)) {
                $feedbackMessage = 'Error: File not found.';
                $feedbackType = 'error';
            } elseif (!is_writable($filePath)) {
                $feedbackMessage = 'Error: File is not writable. Please check file permissions on your server.';
                $feedbackType = 'error';
            } else {
                file_put_contents($filePath, $content);
                $feedbackMessage = 'File "' . htmlspecialchars($safeFilename) . '" updated successfully.';
                $feedbackType = 'success';
            }
        }
        
        // --- FILE DELETION HANDLING ---
        if (isset($_POST['delete_file'])) {
            $filename = $_POST['filename'] ?? '';
            $safeFilename = basename($filename);
            $filePath = $realPath . DIRECTORY_SEPARATOR . $safeFilename;

            if (!file_exists($filePath)) {
                $feedbackMessage = 'Error: File not found.';
                $feedbackType = 'error';
            } elseif (!is_writable($filePath)) {
                $feedbackMessage = 'Error: File is not deletable. Please check file permissions on your server.';
                $feedbackType = 'error';
            } else {
                unlink($filePath);
                $feedbackMessage = 'File "' . htmlspecialchars($safeFilename) . '" deleted successfully.';
                $feedbackType = 'success';
            }
        }
    }
}

// --- AJAX: FETCH FILE CONTENT (for editing) ---
if ($isLoggedIn && isset($_GET['fetch_content']) && isset($_GET['file'])) {
    header('Content-Type: text/plain');
    $filename = basename($_GET['file']);
    $filePath = $realPath . DIRECTORY_SEPARATOR . $filename;
    if (file_exists($filePath) && is_readable($filePath)) {
        echo file_get_contents($filePath);
    } else {
        echo 'Error: File not found or not readable.';
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHPIndex - File Lister</title>
    <!-- Tailwind CSS for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Prism.js CSS for Syntax Highlighting -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet" />
    <!-- Configuration for class-based dark mode -->
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
    <style>
        .copy-button.copied { background-color: #28a745; color: white; }
        details > summary { list-style: none; }
        details > summary::-webkit-details-marker { display: none; }
        details > summary::before { content: '►'; margin-right: 0.5rem; font-size: 0.8em; transition: transform 0.2s ease-in-out; }
        details[open] > summary::before { transform: rotate(90deg); }
        .line-numbers .line-numbers-rows { user-select: none; }
        .modal-overlay { transition: opacity 0.3s ease; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 font-sans">

    <div class="container mx-auto p-4 sm:p-6 lg:p-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-4 border-b dark:border-gray-700 pb-4">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-gray-200">PHPIndex</h1>
                <div class="flex items-center gap-2">
                    <?php if (WRITE_PROTECTION_ENABLED): ?>
                        <?php if($isLoggedIn): ?>
                            <a href="?logout=true" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-md text-sm">Logout</a>
                        <?php else: ?>
                            <button onclick="toggleLoginModal(true)" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md text-sm">Login</button>
                        <?php endif; ?>
                    <?php endif; ?>
                    <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm p-2.5">
                        <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                        <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm-.707 7.072l.707-.707a1 1 0 10-1.414-1.414l-.707.707a1 1 0 101.414 1.414zM3 11a1 1 0 100 2h1a1 1 0 100-2H3z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                    </button>
                </div>
            </div>

            <?php if (!empty($feedbackMessage)): ?>
                <div class="mb-4 p-4 rounded-md <?= $feedbackType === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' ?>">
                    <?= $feedbackMessage ?>
                </div>
            <?php endif; ?>
            
            <p class="text-gray-600 dark:text-gray-400 mb-6">This page lists files in the current directory. You can view content, get download/execute commands for Wget, Curl, or PowerShell, or filter the list using wildcards (e.g., `*.sh`).</p>
            
            <div class="flex flex-col sm:flex-row gap-4 mb-6">
                <div class="flex-grow">
                    <label for="filterInput" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Filter files:</label>
                    <input type="text" id="filterInput" onkeyup="filterFiles()" placeholder="e.g., my_script or *.sh" class="w-full p-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:placeholder-gray-400">
                </div>
                <?php if ($isLoggedIn && WRITE_PROTECTION_ENABLED): ?>
                    <div class="flex-shrink-0 self-end">
                        <button onclick="toggleCreateModal(true)" class="w-full sm:w-auto bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-md">Create New File</button>
                    </div>
                <?php endif; ?>
            </div>

            <?php
                // --- BREADCRUMB NAVIGATION ---
                echo '<nav class="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-4 p-2 bg-gray-50 dark:bg-gray-700 rounded-md" aria-label="Breadcrumb">';
                echo '<ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">';
                echo '<li class="inline-flex items-center">';
                echo '<a href="?path=." class="inline-flex items-center font-medium hover:text-blue-600 dark:hover:text-blue-400">PHPIndex</a>';
                echo '</li>';
                $pathParts = ($relativePath !== '.' && $relativePath !== '') ? explode('/', $relativePath) : [];
                $currentPath = '';
                foreach ($pathParts as $part) {
                    $currentPath .= $part . '/';
                    echo '<li><div class="flex items-center"><span class="mx-2">/</span>';
                    echo '<a href="?path=' . urlencode(rtrim($currentPath, '/')) . '" class="font-medium hover:text-blue-600 dark:hover:text-blue-400">' . htmlspecialchars($part) . '</a>';
                    echo '</div></li>';
                }
                echo '</ol></nav>';
            ?>
            
            <div id="file-list" class="space-y-4">
                <?php
                // --- URL & DIRECTORY SCANNING ---
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'];
                $scriptDir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                $currentSubDir = ($relativePath === '.' || $relativePath === '') ? '' : '/' . str_replace('\\', '/', $relativePath);
                $fullPathUrl = "{$protocol}://{$host}{$scriptDir}{$currentSubDir}";

                $items = scandir($realPath);
                $directories = [];
                $files = [];

                if ($items !== false) {
                    foreach ($items as $item) {
                        if ($item === '.' || $item === '..' || in_array($item, $excludeList)) {
                            continue;
                        }
                        $itemPath = $realPath . DIRECTORY_SEPARATOR . $item;
                        if (is_dir($itemPath)) {
                            $directories[] = $item;
                        } else {
                             $extension = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                             $extensionWithDot = !empty($extension) ? '.' . $extension : '';
                             if (!is_file($itemPath) || ($extensionWithDot && in_array($extensionWithDot, $excludeList))) {
                                continue;
                             }
                             $files[] = $item;
                        }
                    }
                }
                
                // --- DISPLAY DIRECTORIES ---
                foreach ($directories as $dir) {
                    $safeDir = htmlspecialchars($dir, ENT_QUOTES, 'UTF-8');
                    $dirPath = ltrim(rtrim($relativePath, '/') . '/' . $dir, './');
                ?>
                    <a href="?path=<?= urlencode($dirPath) ?>" class="file-entry-card block border border-gray-200 dark:border-gray-700 rounded-lg p-4 transition hover:bg-gray-50 dark:hover:bg-gray-700" data-filename="<?= $safeDir ?>">
                        <div class="font-mono text-gray-700 dark:text-gray-300 break-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2 text-yellow-500" viewBox="0 0 20 20" fill="currentColor"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" /></svg>
                            <?= $safeDir ?>
                        </div>
                    </a>
                <?php
                }

                // --- DISPLAY FILES ---
                $fileCount = 0;
                foreach ($files as $file) {
                    $fileCount++;
                    $safeFile = htmlspecialchars($file, ENT_QUOTES, 'UTF-8');
                    $fullUrl = rtrim($fullPathUrl, '/') . '/' . rawurlencode($file);
                    $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $isPowershellFile = in_array($fileExtension, $powershellExtensions);
                    $wgetDownloadCommand = "wget '" . $fullUrl . "'";
                    $wgetExecCommand = "wget -O - '" . $fullUrl . "' | bash";
                    $curlDownloadCommand = "curl -o '" . $safeFile . "' -L '" . $fullUrl . "'";
                    $curlExecCommand = "curl -sL '" . $fullUrl . "' | bash";
                    $psDownloadCommand = "Invoke-WebRequest -Uri '" . $fullUrl . "' -OutFile '" . $safeFile . "'";
                    $psExecCommand = "Invoke-RestMethod -Uri '" . $fullUrl . "' | Invoke-Expression";
                    $initialCommand = $isPowershellFile ? $psDownloadCommand : $wgetDownloadCommand;
                    $langMap = ['sh' => 'bash', 'js' => 'javascript', 'py' => 'python', 'md' => 'markdown'];
                    $languageClass = isset($langMap[$fileExtension]) ? $langMap[$fileExtension] : $fileExtension;
                ?>
                    <div class="file-entry-card border border-gray-200 dark:border-gray-700 rounded-lg p-4" data-filename="<?= $safeFile ?>">
                         <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="font-mono text-gray-700 dark:text-gray-300 break-all lg:w-1/4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2 text-gray-400 dark:text-gray-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" /></svg>
                                <a href="<?= $fullUrl ?>"><?= $safeFile ?></a>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full lg:w-3/4" id="command-ui-<?= $fileCount ?>">
                                <div class="flex-shrink-0 flex items-center gap-2">
                                    <select class="tool-select p-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" onchange="updateCommand(this)">
                                        <option value="wget" <?= !$isPowershellFile ? 'selected' : '' ?>>Wget</option>
                                        <option value="curl">Curl</option>
                                        <option value="ps" <?= $isPowershellFile ? 'selected' : '' ?>>PowerShell</option>
                                    </select>
                                    <select class="action-select p-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" onchange="updateCommand(this)">
                                        <option value="download" selected>Download</option>
                                        <option value="execute">Execute</option>
                                    </select>
                                </div>
                                <div class="flex items-center gap-2 w-full command-display"
                                     data-wget-download="<?= htmlspecialchars($wgetDownloadCommand) ?>" data-wget-execute="<?= htmlspecialchars($wgetExecCommand) ?>"
                                     data-curl-download="<?= htmlspecialchars($curlDownloadCommand) ?>" data-curl-execute="<?= htmlspecialchars($curlExecCommand) ?>"
                                     data-ps-download="<?= htmlspecialchars($psDownloadCommand) ?>" data-ps-execute="<?= htmlspecialchars($psExecCommand) ?>">
                                    <pre class="bg-gray-800 dark:bg-gray-900 text-white text-sm p-2 rounded-md overflow-x-auto w-full"><code class="command-code"><?= htmlspecialchars($initialCommand) ?></code></pre>
                                    <button class="copy-button bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-3 rounded-md transition-all duration-200 flex-shrink-0" onclick="copyCommand(this)" title="Copy to clipboard">Copy</button>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2 items-center">
                            <details class="w-full">
                                <summary class="cursor-pointer text-blue-600 dark:text-blue-400 hover:underline select-none inline-block">View Content</summary>
                                <div class="mt-2 border dark:border-gray-600 rounded-md">
                                    <pre class="text-sm overflow-auto" style="max-height: 400px;"><code class="language-<?= $languageClass ?>"><?= htmlspecialchars(file_get_contents($realPath . DIRECTORY_SEPARATOR . $file)); ?></code></pre>
                                </div>
                            </details>
                             <?php if ($isLoggedIn && WRITE_PROTECTION_ENABLED): ?>
                                <div class="ml-auto flex gap-2">
                                    <button onclick="openEditModal('<?= $safeFile ?>')" class="text-sm bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-1 px-3 rounded-md">Edit</button>
                                    <button onclick="openDeleteModal('<?= $safeFile ?>')" class="text-sm bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-3 rounded-md">Delete</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php
                }

                echo '<div id="no-results" class="text-center text-gray-500 dark:text-gray-400 p-8 bg-gray-50 dark:bg-gray-700 rounded-lg" style="display: none;"><p>No items found that match your filter.</p></div>';

                if (count($directories) === 0 && count($files) === 0) {
                    echo '<div class="text-center text-gray-500 dark:text-gray-400 p-8 bg-gray-50 dark:bg-gray-700 rounded-lg"><p>This directory is empty.</p></div>';
                }
                ?>
            </div>
            
            <footer class="text-center text-sm text-gray-500 dark:text-gray-400 mt-8 pt-6 border-t dark:border-gray-700">
                <div class="flex items-center justify-center gap-4">
                    <a href="https://github.com/danmed/PHPIndex" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 hover:text-blue-500 dark:hover:text-blue-400 transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12c0 4.418 2.865 8.168 6.839 9.492.5.092.682-.217.682-.482 0-.237-.009-.868-.014-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.031-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.03 1.595 1.03 2.688 0 3.848-2.338 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.001 10.001 0 0022 12c0-5.523-4.477-10-10-10z" clip-rule="evenodd" /></svg>
                        PHPIndex v1.6
                    </a>
                </div>
                <p class="mt-2">Released under the MIT License.</p>
            </footer>

        </div>
    </div>

    <?php if (WRITE_PROTECTION_ENABLED): ?>
    <!-- Login Modal -->
    <div id="login-modal" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 hidden opacity-0">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-sm">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200 mb-4">Admin Login</h2>
            <form method="POST" action="">
                <input type="hidden" name="login" value="1">
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                    <input type="password" name="password" id="password" required class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="toggleLoginModal(false)" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-md">Cancel</button>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md">Login</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create/Edit Modals -->
    <div id="create-modal" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 hidden opacity-0">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-2xl">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200 mb-4">Create New File</h2>
            <form method="POST" action="?path=<?= urlencode($relativePath) ?>">
                <input type="hidden" name="create_file" value="1">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="mb-4">
                    <label for="create-filename" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filename</label>
                    <input type="text" name="filename" id="create-filename" required placeholder="e.g., new_script.sh" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="mb-4">
                    <label for="create-file_content" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Content</label>
                    <textarea name="file_content" id="create-file_content" rows="10" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="toggleCreateModal(false)" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-md">Cancel</button>
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-md">Create File</button>
                </div>
            </form>
        </div>
    </div>

     <div id="edit-modal" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 hidden opacity-0">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-2xl">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200 mb-4">Edit File: <span id="edit-filename-display" class="font-mono"></span></h2>
            <form method="POST" action="?path=<?= urlencode($relativePath) ?>">
                <input type="hidden" name="edit_file" value="1">
                <input type="hidden" name="filename" id="edit-filename-input">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="mb-4">
                    <label for="edit-file-content" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Content</label>
                    <textarea name="file_content" id="edit-file-content" rows="15" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="toggleEditModal(false)" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-md">Cancel</button>
                    <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded-md">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="delete-modal" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 hidden opacity-0">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200 mb-4">Confirm Deletion</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Are you sure you want to delete the file <strong id="delete-filename-display" class="font-mono"></strong>? This action cannot be undone.</p>
            <form method="POST" action="?path=<?= urlencode($relativePath) ?>">
                <input type="hidden" name="delete_file" value="1">
                <input type="hidden" name="filename" id="delete-filename-input">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="toggleDeleteModal(false)" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-md">Cancel</button>
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-md">Delete File</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Prism.js for Syntax Highlighting -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>

    <script>
        function filterFiles() {
            const filterValue = document.getElementById('filterInput').value;
            const entries = document.querySelectorAll('.file-entry-card');
            const noResultsMessage = document.getElementById('no-results');
            let visibleCount = 0;
            const regex = new RegExp(filterValue.replace(/\*/g, '.*'), 'i');

            entries.forEach(card => {
                const filename = card.dataset.filename;
                if (regex.test(filename)) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            noResultsMessage.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        function updateCommand(selectEl) {
            const commandUiContainer = selectEl.closest('[id^="command-ui-"]');
            if (!commandUiContainer) { console.error("Could not find command UI container."); return; }
            const activeTool = commandUiContainer.querySelector('.tool-select').value;
            const activeAction = commandUiContainer.querySelector('.action-select').value;
            const commandDisplayContainer = commandUiContainer.querySelector('.command-display');
            const codeEl = commandDisplayContainer.querySelector('.command-code');
            const commandKey = `data-${activeTool}-${activeAction}`;
            const newCommand = commandDisplayContainer.getAttribute(commandKey);
            if (codeEl && newCommand) { codeEl.innerText = newCommand; }
        }

        function copyCommand(copyButtonEl) {
            const textarea = document.createElement('textarea');
            const codeElement = copyButtonEl.previousElementSibling.querySelector('code');
            if (!codeElement) { console.error('Command code element not found.'); return; }
            textarea.value = codeElement.innerText;
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                copyButtonEl.innerText = 'Copied!';
                copyButtonEl.classList.add('copied');
                setTimeout(() => {
                    copyButtonEl.innerText = 'Copy';
                    copyButtonEl.classList.remove('copied');
                }, 2000);
            } catch (err) {
                console.error('Failed to copy text: ', err);
                alert('Oops, unable to copy');
            } finally {
                document.body.removeChild(textarea);
            }
        }

        const loginModal = document.getElementById('login-modal');
        const createModal = document.getElementById('create-modal');
        const editModal = document.getElementById('edit-modal');
        const deleteModal = document.getElementById('delete-modal');

        function toggleModal(modal, show) {
            if (!modal) return;
            if (show) {
                modal.classList.remove('hidden');
                setTimeout(() => modal.classList.remove('opacity-0'), 10);
            } else {
                modal.classList.add('opacity-0');
                setTimeout(() => modal.classList.add('hidden'), 300);
            }
        }

        function toggleLoginModal(show) { toggleModal(loginModal, show); }
        function toggleCreateModal(show) { toggleModal(createModal, show); }
        function toggleEditModal(show) { toggleModal(editModal, show); }
        function toggleDeleteModal(show) { toggleModal(deleteModal, show); }

        async function openEditModal(filename) {
            const contentTextarea = document.getElementById('edit-file-content');
            contentTextarea.value = 'Loading...';
            document.getElementById('edit-filename-display').innerText = filename;
            document.getElementById('edit-filename-input').value = filename;
            toggleEditModal(true);

            try {
                const response = await fetch(`?fetch_content=true&path=<?= urlencode($relativePath) ?>&file=${encodeURIComponent(filename)}`);
                if(response.ok) {
                    const text = await response.text();
                    contentTextarea.value = text;
                } else {
                    contentTextarea.value = 'Error loading file content.';
                }
            } catch (error) {
                console.error('Fetch error:', error);
                contentTextarea.value = 'Error loading file content.';
            }
        }

        function openDeleteModal(filename) {
            document.getElementById('delete-filename-display').innerText = filename;
            document.getElementById('delete-filename-input').value = filename;
            toggleDeleteModal(true);
        }
        
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

        function initializeTheme() {
            if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
                themeToggleLightIcon.classList.remove('hidden');
            } else {
                document.documentElement.classList.remove('dark');
                themeToggleDarkIcon.classList.remove('hidden');
            }
        }
        
        themeToggleBtn.addEventListener('click', function() {
            themeToggleDarkIcon.classList.toggle('hidden');
            themeToggleLightIcon.classList.toggle('hidden');
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            }
        });

        initializeTheme();
    </script>
</body>
</html>

