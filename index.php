<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHPIndex - File Lister</title>
    <!-- Tailwind CSS for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Configuration for class-based dark mode -->
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
    <style>
        /* A little extra style for the copy feedback */
        .copy-button.copied {
            background-color: #28a745; /* Green */
            color: white;
        }
        /* Style for the details marker */
        details > summary {
            list-style: none;
        }
        details > summary::-webkit-details-marker {
            display: none;
        }
        details > summary::before {
            content: '►';
            margin-right: 0.5rem;
            font-size: 0.8em;
            transition: transform 0.2s ease-in-out;
        }
        details[open] > summary::before {
            transform: rotate(90deg);
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 font-sans">

    <div class="container mx-auto p-4 sm:p-6 lg:p-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-4 border-b dark:border-gray-700 pb-4">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-gray-200">PHPIndex</h1>
                <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm p-2.5">
                    <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                    <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm-.707 7.072l.707-.707a1 1 0 10-1.414-1.414l-.707.707a1 1 0 101.414 1.414zM3 11a1 1 0 100 2h1a1 1 0 100-2H3z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                </button>
            </div>
            <p class="text-gray-600 dark:text-gray-400 mb-6">This page lists files in the current directory. You can view content, get download/execute commands for Wget, Curl, or PowerShell, or filter the list using wildcards (e.g., `*.sh`).</p>

            <!-- GUI Filter Input -->
            <div class="mb-6">
                <label for="filterInput" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Filter files:</label>
                <input type="text" id="filterInput" onkeyup="filterFiles()" placeholder="e.g., my_script or *.sh" class="w-full p-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:placeholder-gray-400">
            </div>

            <div id="file-list" class="space-y-4">
                <?php
                // --- PHP SCRIPT START ---

                // Version 1.1

                // --- CONFIGURATION ---
                // Add filenames or extensions (with a leading dot like '.css') to this array to exclude them.
                $excludeList = [
                    'index.php',
                    'PHPIndex.php', // Also exclude itself by its new name
                    '.DS_Store',
                ];

                // Determine the base URL for constructing download links.
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'];
                $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                $baseUrl = "{$protocol}://{$host}{$path}/";

                $files = scandir('.');

                if ($files !== false) {
                    $fileCount = 0;
                    foreach ($files as $file) {
                        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        $extensionWithDot = !empty($extension) ? '.' . $extension : '';

                        // --- FILTERING LOGIC ---
                        if (
                            !is_file($file) ||
                            in_array($file, $excludeList) ||
                            ($extensionWithDot && in_array($extensionWithDot, $excludeList))
                        ) {
                            continue; // Skip this item
                        }
                        
                        $fileCount++;
                        $safeFile = htmlspecialchars($file, ENT_QUOTES, 'UTF-8');
                        $fullUrl = $baseUrl . $safeFile;

                        // --- GENERATE ALL COMMANDS ---
                        // Wget
                        $wgetDownloadCommand = "wget '" . $fullUrl . "'";
                        $wgetExecCommand = "wget -O - '" . $fullUrl . "' | bash";
                        // Curl
                        $curlDownloadCommand = "curl -o '" . $safeFile . "' -L '" . $fullUrl . "'";
                        $curlExecCommand = "curl -sL '" . $fullUrl . "' | bash";
                        // PowerShell
                        $psDownloadCommand = "Invoke-WebRequest -Uri '" . $fullUrl . "' -OutFile '" . $safeFile . "'";
                        $psExecCommand = "Invoke-RestMethod -Uri '" . $fullUrl . "' | Invoke-Expression";
                ?>
                            <!-- File Entry Card -->
                            <div class="file-entry-card border border-gray-200 dark:border-gray-700 rounded-lg p-4 transition hover:bg-gray-50 dark:hover:bg-gray-700" data-filename="<?= $safeFile ?>">
                                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                                    <!-- File Name -->
                                    <div class="font-mono text-gray-700 dark:text-gray-300 break-all lg:w-1/4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2 text-gray-400 dark:text-gray-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" /></svg>
                                        <?= $safeFile ?>
                                    </div>

                                    <!-- NEW Command UI with Dropdowns -->
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full lg:w-3/4" id="command-ui-<?= $fileCount ?>">
                                        <!-- Command Selectors -->
                                        <div class="flex-shrink-0 flex items-center gap-2">
                                            <select class="tool-select p-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" onchange="updateCommand(this)">
                                                <option value="wget" selected>Wget</option>
                                                <option value="curl">Curl</option>
                                                <option value="ps">PowerShell</option>
                                            </select>
                                            <select class="action-select p-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" onchange="updateCommand(this)">
                                                <option value="download" selected>Download</option>
                                                <option value="execute">Execute</option>
                                            </select>
                                        </div>
                                        <!-- Command Display and Copy Button -->
                                        <div class="flex items-center gap-2 w-full command-display"
                                             data-wget-download="<?= htmlspecialchars($wgetDownloadCommand) ?>"
                                             data-wget-execute="<?= htmlspecialchars($wgetExecCommand) ?>"
                                             data-curl-download="<?= htmlspecialchars($curlDownloadCommand) ?>"
                                             data-curl-execute="<?= htmlspecialchars($curlExecCommand) ?>"
                                             data-ps-download="<?= htmlspecialchars($psDownloadCommand) ?>"
                                             data-ps-execute="<?= htmlspecialchars($psExecCommand) ?>">
                                            <pre class="bg-gray-800 dark:bg-gray-900 text-white text-sm p-2 rounded-md overflow-x-auto w-full"><code class="command-code"><?= htmlspecialchars($wgetDownloadCommand) ?></code></pre>
                                            <button 
                                                class="copy-button bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-3 rounded-md transition-all duration-200 flex-shrink-0"
                                                onclick="copyCommand(this)"
                                                title="Copy to clipboard">
                                                Copy
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Collapsible File Content Viewer -->
                                <details class="mt-4">
                                    <summary class="cursor-pointer text-blue-600 dark:text-blue-400 hover:underline select-none">View Content</summary>
                                    <div class="mt-2 p-4 bg-gray-50 dark:bg-gray-700 border dark:border-gray-600 rounded-md">
                                        <pre class="text-sm overflow-auto text-gray-800 dark:text-gray-200" style="max-height: 400px;"><code><?= htmlspecialchars(file_get_contents($file)); ?></code></pre>
                                    </div>
                                </details>
                            </div>
                <?php
                    }

                    echo '<div id="no-results" class="text-center text-gray-500 dark:text-gray-400 p-8 bg-gray-50 dark:bg-gray-700 rounded-lg" style="display: none;"><p>No files found that match your filter.</p></div>';

                    if ($fileCount === 0) {
                        echo '<div class="text-center text-gray-500 dark:text-gray-400 p-8 bg-gray-50 dark:bg-gray-700 rounded-lg"><p>No files found that match the initial filter criteria.</p></div>';
                    }
                } else {
                    echo '<div class="text-center text-red-500 p-8 bg-red-50 dark:bg-red-900 dark:text-red-300 rounded-lg"><p>Error: Could not read the directory.</p></div>';
                }
                // --- PHP SCRIPT END ---
                ?>
            </div>
            
            <!-- Footer -->
            <footer class="text-center text-sm text-gray-500 dark:text-gray-400 mt-8 pt-6 border-t dark:border-gray-700">
                <div class="flex items-center justify-center gap-4">
                    <a href="https://github.com/danmed/PHPIndex" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 hover:text-blue-500 dark:hover:text-blue-400 transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12c0 4.418 2.865 8.168 6.839 9.492.5.092.682-.217.682-.482 0-.237-.009-.868-.014-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.031-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.03 1.595 1.03 2.688 0 3.848-2.338 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.001 10.001 0 0022 12c0-5.523-4.477-10-10-10z" clip-rule="evenodd" /></svg>
                        PHPIndex v1.1
                    </a>
                </div>
                <p class="mt-2">Released under the MIT License.</p>
            </footer>

        </div>
    </div>

    <script>
        /**
         * Filters the list of files based on user input with wildcard support.
         */
        function filterFiles() {
            const filterValue = document.getElementById('filterInput').value;
            const fileCards = document.querySelectorAll('.file-entry-card');
            const noResultsMessage = document.getElementById('no-results');
            let visibleCount = 0;

            const regex = new RegExp(filterValue.replace(/\*/g, '.*'), 'i');

            fileCards.forEach(card => {
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

        /**
         * Updates the displayed command when a dropdown selection changes.
         * @param {HTMLElement} selectEl The select element that was changed.
         */
        function updateCommand(selectEl) {
            const commandUiContainer = selectEl.closest('[id^="command-ui-"]');
            if (!commandUiContainer) {
                console.error("Could not find command UI container.");
                return;
            }
            
            // Determine active tool and action from dropdown values
            const activeTool = commandUiContainer.querySelector('.tool-select').value;
            const activeAction = commandUiContainer.querySelector('.action-select').value;
            
            // Get the elements for displaying the command
            const commandDisplayContainer = commandUiContainer.querySelector('.command-display');
            const codeEl = commandDisplayContainer.querySelector('.command-code');

            // Construct the data attribute key and get the new command
            const commandKey = `data-${activeTool}-${activeAction}`;
            const newCommand = commandDisplayContainer.getAttribute(commandKey);
            
            if (codeEl && newCommand) {
                codeEl.innerText = newCommand;
            }
        }

        /**
         * Copies the currently displayed command to the clipboard.
         * @param {HTMLElement} copyButtonEl The copy button element that was clicked.
         */
        function copyCommand(copyButtonEl) {
            const textarea = document.createElement('textarea');
            const codeElement = copyButtonEl.previousElementSibling.querySelector('code');
            
            if (!codeElement) {
                console.error('Command code element not found.');
                return;
            }

            textarea.value = codeElement.innerText;
            textarea.style.position = 'fixed';
            textarea.style.opacity = 0;
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

        // --- Dark Mode Toggle Script ---
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

        function initializeTheme() {
            if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
                themeToggleLightIcon.classList.remove('hidden');
            } else {
                document.documentElement.classList.remove('dark'); // Ensure dark class is removed if not dark
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

        // Set initial theme on page load
        initializeTheme();

    </script>
</body>
</html>

